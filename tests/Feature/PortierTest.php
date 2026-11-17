<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PortierOidc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Connexion facultative via Portier, avec un faux fournisseur
 * (Http::fake + clé RSA générée pour le test).
 */
class PortierTest extends TestCase
{
    use RefreshDatabase;

    private const ISSUER = 'https://portier.test';

    private $clePrivee;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.portier.actif' => true,
            'services.portier.issuer' => self::ISSUER,
            'services.portier.client_id' => 'loan',
            'services.portier.client_secret' => 'secret-de-test',
            'services.portier.redirect' => null,
        ]);

        $this->clePrivee = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    }

    private function jwks(): array
    {
        $rsa = openssl_pkey_get_details($this->clePrivee)['rsa'];

        return ['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => 'cle-test',
            'n' => PortierOidc::base64url($rsa['n']),
            'e' => PortierOidc::base64url($rsa['e']),
        ]]];
    }

    private function jeton(array $claims, $cle = null): string
    {
        $h = PortierOidc::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'cle-test']));
        $p = PortierOidc::base64url(json_encode($claims));
        openssl_sign("$h.$p", $signature, $cle ?? $this->clePrivee, OPENSSL_ALGO_SHA256);

        return "$h.$p.".PortierOidc::base64url($signature);
    }

    /** Départ vers Portier ; renvoie [state, nonce] gardés en session. */
    private function depart(): array
    {
        $reponse = $this->get('/auth/portier');
        $reponse->assertRedirect();
        $this->assertStringStartsWith(self::ISSUER.'/oidc/autoriser?', $reponse->headers->get('Location'));

        $session = session('portier');

        return [$session['state'], $session['nonce']];
    }

    private function fauxPortier(array $claims, $cle = null): void
    {
        Http::fake([
            self::ISSUER.'/oidc/jeton' => Http::response(['access_token' => 'x', 'token_type' => 'Bearer', 'id_token' => $this->jeton($claims, $cle)]),
            self::ISSUER.'/oidc/cles' => Http::response($this->jwks()),
        ]);
    }

    private function claims(string $nonce, array $autres = []): array
    {
        return array_merge([
            'iss' => self::ISSUER, 'aud' => 'loan', 'azp' => 'loan', 'sub' => 'charles',
            'preferred_username' => 'charles', 'name' => 'charles',
            'email' => 'charles@example.com', 'email_verified' => true,
            'nonce' => $nonce, 'iat' => time(), 'exp' => time() + 600,
        ], $autres);
    }

    public function test_desactive_routes_en_404_et_page_sans_portier(): void
    {
        config(['services.portier.actif' => false]);

        $this->get('/auth/portier')->assertNotFound();
        $this->get('/auth/portier/retour?state=a&code=b')->assertNotFound();
        $this->postJson('/api/auth/portier/echange', ['code' => 'x'])->assertNotFound();
        $this->get('/login')->assertOk()->assertSee('window.portierActif = false', false);
    }

    public function test_connexion_par_mot_de_passe_inchangee(): void
    {
        User::factory()->create(['email' => 'charles@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'charles@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['userToken', 'user']]);
    }

    public function test_depart_envoie_pkce_state_et_nonce(): void
    {
        $location = $this->get('/auth/portier')->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        $this->assertSame('code', $q['response_type']);
        $this->assertSame('loan', $q['client_id']);
        $this->assertSame(url('/auth/portier/retour'), $q['redirect_uri']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(PortierOidc::base64url(hash('sha256', session('portier.verificateur'), true)), $q['code_challenge']);
        $this->assertSame(session('portier.state'), $q['state']);
        $this->assertSame(session('portier.nonce'), $q['nonce']);
    }

    public function test_state_invalide_refuse(): void
    {
        [, $nonce] = $this->depart();
        $this->fauxPortier($this->claims($nonce));

        $this->get('/auth/portier/retour?state=mauvais&code=abc')
            ->assertRedirect('/login?portier_erreur=etat');
        Http::assertNothingSent();
    }

    public function test_compte_inconnu_refuse_sans_creation(): void
    {
        [$state, $nonce] = $this->depart();
        $this->fauxPortier($this->claims($nonce, ['email' => 'inconnu@example.com']));

        $this->get("/auth/portier/retour?state=$state&code=abc")
            ->assertRedirect('/login?portier_erreur=compte_inconnu');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_non_verifie_refuse(): void
    {
        User::factory()->create(['email' => 'charles@example.com']);
        [$state, $nonce] = $this->depart();
        $this->fauxPortier($this->claims($nonce, ['email_verified' => false]));

        $this->get("/auth/portier/retour?state=$state&code=abc")
            ->assertRedirect('/login?portier_erreur=sans_email');
    }

    public function test_signature_invalide_refusee(): void
    {
        User::factory()->create(['email' => 'charles@example.com']);
        [$state, $nonce] = $this->depart();
        $autreCle = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->fauxPortier($this->claims($nonce), $autreCle);

        $this->get("/auth/portier/retour?state=$state&code=abc")
            ->assertRedirect('/login?portier_erreur=echec');
    }

    public function test_nonce_audience_ou_expiration_invalides_refuses(): void
    {
        User::factory()->create(['email' => 'charles@example.com']);

        foreach ([['nonce' => 'autre'], ['aud' => 'autre-appli'], ['exp' => time() - 3600], ['iss' => 'https://ailleurs.test']] as $faux) {
            [$state, $nonce] = $this->depart();
            $this->fauxPortier($this->claims($nonce, $faux));

            $this->get("/auth/portier/retour?state=$state&code=abc")
                ->assertRedirect('/login?portier_erreur=echec');
        }
    }

    public function test_refus_de_portier(): void
    {
        [$state] = $this->depart();

        $this->get("/auth/portier/retour?state=$state&error=access_denied")
            ->assertRedirect('/login?portier_erreur=refus');
    }

    public function test_connexion_reussie_puis_code_a_usage_unique(): void
    {
        $user = User::factory()->create(['email' => 'charles@example.com']);
        [$state, $nonce] = $this->depart();
        $this->fauxPortier($this->claims($nonce));

        $location = $this->get("/auth/portier/retour?state=$state&code=abc")->headers->get('Location');
        $this->assertStringContainsString('/login?portier=', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        Http::assertSent(fn ($r) => $r->url() === self::ISSUER.'/oidc/jeton'
            && $r['client_secret'] === 'secret-de-test'
            && $r['code'] === 'abc'
            && $r['code_verifier'] !== null);

        $token = $this->postJson('/api/auth/portier/echange', ['code' => $q['portier']])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->json('data.userToken');

        $this->withToken($token)->getJson('/api/auth/data')->assertOk()->assertJsonPath('data.email', 'charles@example.com');

        // Le code ne sert qu'une fois.
        $this->postJson('/api/auth/portier/echange', ['code' => $q['portier']])->assertUnauthorized();
    }

    public function test_jwk_vers_pem(): void
    {
        $rsa = openssl_pkey_get_details($this->clePrivee);
        $pem = PortierOidc::jwkVersPem(PortierOidc::base64url($rsa['rsa']['n']), PortierOidc::base64url($rsa['rsa']['e']));

        $this->assertSame(trim($rsa['key']), trim($pem));
    }
}
