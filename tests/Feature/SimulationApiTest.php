<?php

namespace Tests\Feature;

use App\Models\Simulation;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SimulationApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'amount' => 5000000,
        'annual_rate' => 6.5,
        'duration_months' => 60,
        'method' => 'annuity',
    ];

    public function test_guest_can_create_a_simulation(): void
    {
        $response = $this->postJson('/api/simulations', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonPath('data.simulation.params.amount', 5000000)
            ->assertJsonPath('data.quota.plan', 'guest')
            ->assertJsonPath('data.quota.used', 1);

        $this->assertCount(60, $response->json('data.simulation.schedule'));
        $this->assertDatabaseCount('simulations', 1);
        $this->assertDatabaseHas('simulations', ['user_id' => null]);
    }

    public function test_guest_is_blocked_after_two_simulations_per_day(): void
    {
        $this->postJson('/api/simulations', $this->validPayload)->assertStatus(201);
        $this->postJson('/api/simulations', $this->validPayload)->assertStatus(201);

        $this->postJson('/api/simulations', $this->validPayload)
            ->assertStatus(429)
            ->assertJsonPath('errors.quota_exceeded', true);
    }

    public function test_authenticated_user_has_five_simulations_per_day(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < QuotaService::FREE_DAILY_LIMIT; $i++) {
            $this->postJson('/api/simulations', $this->validPayload)->assertStatus(201);
        }

        $this->postJson('/api/simulations', $this->validPayload)->assertStatus(429);
    }

    public function test_premium_user_is_unlimited(): void
    {
        $user = User::factory()->create(['is_premium' => true]);
        Sanctum::actingAs($user);

        for ($i = 0; $i < QuotaService::FREE_DAILY_LIMIT + 2; $i++) {
            $this->postJson('/api/simulations', $this->validPayload)->assertStatus(201);
        }

        $this->getJson('/api/simulations/quota')
            ->assertOk()
            ->assertJsonPath('data.plan', 'premium')
            ->assertJsonPath('data.limit', null);
    }

    public function test_quota_endpoint_for_guest(): void
    {
        $this->getJson('/api/simulations/quota')
            ->assertOk()
            ->assertJsonPath('data.plan', 'guest')
            ->assertJsonPath('data.limit', QuotaService::GUEST_DAILY_LIMIT)
            ->assertJsonPath('data.remaining', QuotaService::GUEST_DAILY_LIMIT);
    }

    public function test_validation_errors_are_returned(): void
    {
        $this->postJson('/api/simulations', ['amount' => -5])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount', 'annual_rate', 'duration_months', 'method']]);
    }

    public function test_user_can_list_and_view_own_simulations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/simulations', $this->validPayload)->json('data.simulation');

        $this->getJson('/api/simulations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $created['id']);

        $this->getJson('/api/simulations/' . $created['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $created['id']);
    }

    public function test_user_cannot_view_someone_elses_simulation(): void
    {
        $owner = User::factory()->create();
        $simulation = Simulation::create([
            'user_id' => $owner->id,
            'type' => Simulation::TYPE_AMORTIZATION,
            'params' => [],
            'results' => ['summary' => [], 'schedule' => []],
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/simulations/' . $simulation->id)->assertStatus(403);
        $this->deleteJson('/api/simulations/' . $simulation->id)->assertStatus(403);
        $this->assertDatabaseHas('simulations', ['id' => $simulation->id]);
    }

    public function test_user_can_delete_own_simulation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/simulations', $this->validPayload)->json('data.simulation.id');

        $this->deleteJson('/api/simulations/' . $id)->assertOk();
        $this->assertDatabaseMissing('simulations', ['id' => $id]);
    }

    public function test_user_can_export_own_simulation_in_all_formats(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/simulations', $this->validPayload)->json('data.simulation.id');

        $expected = [
            'pdf' => 'application/pdf',
            'word' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        foreach ($expected as $format => $mime) {
            $response = $this->get("/api/simulations/$id/export/$format");
            $response->assertOk();
            $this->assertStringContainsString($mime, $response->headers->get('content-type'), "Format $format");
            $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        }

        $this->get("/api/simulations/$id/export/csv")->assertStatus(422);
    }

    public function test_guest_cannot_export(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/simulations', $this->validPayload)->json('data.simulation.id');

        // Un autre utilisateur ne peut pas exporter la simulation d'autrui
        Sanctum::actingAs(User::factory()->create());
        $this->get("/api/simulations/$id/export/pdf")->assertStatus(403);
    }

    public function test_share_link_lifecycle(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/simulations', $this->validPayload)->json('data.simulation.id');

        // Génération du lien
        $share = $this->postJson("/api/simulations/$id/share")->assertOk()->json('data');
        $this->assertNotEmpty($share['share_token']);

        // Consultation publique (endpoint sans authentification requise)
        $this->getJson('/api/shared/' . $share['share_token'])
            ->assertOk()
            ->assertJsonPath('data.params.amount', 5000000)
            ->assertJsonMissingPath('data.user_id');

        // Révocation
        $this->deleteJson("/api/simulations/$id/share")->assertOk();
        $this->getJson('/api/shared/' . $share['share_token'])->assertStatus(404);
    }
}
