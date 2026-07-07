<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Simulation;
use App\Services\LoanCalculator;
use App\Services\QuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Traits\CustomResponseTrait;

/**
 * @group Simulations
 *
 * EndPoints de simulation de prêts (tableau d'amortissement, quotas, historique)
 */
class SimulationController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private readonly LoanCalculator $calculator,
        private readonly QuotaService $quota,
    ) {
    }

    /**
     * État du quota du visiteur courant (anonyme ou connecté).
     */
    public function quota(Request $request)
    {
        return $this->responseOk(
            $this->quota->status($request->user('sanctum'), $request->ip())
        );
    }

    /**
     * Calcule une simulation d'amortissement, l'enregistre et décompte le quota.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000|max:100000000000',
            'annual_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1|max:600',
            'method' => 'required|in:' . implode(',', LoanCalculator::METHODS),
            'insurance_rate' => 'nullable|numeric|min:0|max:20',
            'start_date' => 'nullable|date',
            'title' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $user = $request->user('sanctum');
        $quotaStatus = $this->quota->status($user, $request->ip());

        if (!$quotaStatus['allowed']) {
            return $this->responseError([
                'quota' => $user
                    ? 'Quota journalier atteint (' . QuotaService::FREE_DAILY_LIMIT . ' simulations/jour). Passez Premium pour un accès illimité.'
                    : 'Quota journalier atteint (' . QuotaService::GUEST_DAILY_LIMIT . ' simulations/jour). Connectez-vous pour en obtenir 5 par jour.',
                'quota_exceeded' => true,
            ], 429);
        }

        $validated = $validator->validated();
        $results = $this->calculator->amortization($validated);

        $simulation = Simulation::create([
            'user_id' => $user?->id,
            'type' => Simulation::TYPE_AMORTIZATION,
            'title' => $validated['title'] ?? null,
            'params' => $results['params'],
            'results' => [
                'summary' => $results['summary'],
                'schedule' => $results['schedule'],
            ],
            'ip_address' => $user ? null : $request->ip(),
        ]);

        return $this->responseOk([
            'simulation' => [
                'id' => $simulation->id,
                'type' => $simulation->type,
                'title' => $simulation->title,
                'params' => $results['params'],
                'summary' => $results['summary'],
                'schedule' => $results['schedule'],
                'created_at' => $simulation->created_at->toISOString(),
            ],
            'quota' => $this->quota->status($user, $request->ip()),
        ], ['Simulation calculée avec succès.'], 201);
    }

    /**
     * Historique des simulations de l'utilisateur connecté (sans les tableaux détaillés).
     */
    public function index(Request $request)
    {
        $simulations = Simulation::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Simulation $s) => [
                'id' => $s->id,
                'type' => $s->type,
                'title' => $s->title,
                'params' => $s->params,
                'summary' => $s->results['summary'] ?? null,
                'share_token' => $s->share_token,
                'created_at' => $s->created_at->toISOString(),
            ]);

        return $this->responseOk($simulations);
    }

    /**
     * Détail complet d'une simulation (propriétaire uniquement).
     */
    public function show(Request $request, Simulation $simulation)
    {
        if ($simulation->user_id !== $request->user()->id) {
            return $this->responseError(['forbidden' => 'Accès refusé.'], 403);
        }

        return $this->responseOk([
            'id' => $simulation->id,
            'type' => $simulation->type,
            'title' => $simulation->title,
            'params' => $simulation->params,
            'summary' => $simulation->results['summary'] ?? null,
            'schedule' => $simulation->results['schedule'] ?? [],
            'share_token' => $simulation->share_token,
            'created_at' => $simulation->created_at->toISOString(),
        ]);
    }

    /**
     * Supprime une simulation (propriétaire uniquement).
     */
    public function destroy(Request $request, Simulation $simulation)
    {
        if ($simulation->user_id !== $request->user()->id) {
            return $this->responseError(['forbidden' => 'Accès refusé.'], 403);
        }

        $simulation->delete();

        return $this->responseOk([], ['Simulation supprimée.']);
    }
}
