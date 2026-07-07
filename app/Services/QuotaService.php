<?php

namespace App\Services;

use App\Models\Simulation;
use App\Models\User;

/**
 * Quotas de simulations par jour :
 *  - anonyme (suivi par IP)  : 2 / jour
 *  - connecté gratuit        : 5 / jour
 *  - premium (is_premium)    : illimité
 */
class QuotaService
{
    public const GUEST_DAILY_LIMIT = 2;
    public const FREE_DAILY_LIMIT = 5;

    /**
     * État du quota pour l'utilisateur courant (ou l'IP si anonyme).
     *
     * @return array{plan:string, limit:?int, used:int, remaining:?int, allowed:bool}
     */
    public function status(?User $user, string $ip): array
    {
        if ($user && $user->is_premium) {
            return [
                'plan' => 'premium',
                'limit' => null,
                'used' => $this->usedToday($user, $ip),
                'remaining' => null,
                'allowed' => true,
            ];
        }

        $limit = $user ? self::FREE_DAILY_LIMIT : self::GUEST_DAILY_LIMIT;
        $used = $this->usedToday($user, $ip);

        return [
            'plan' => $user ? 'free' : 'guest',
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'allowed' => $used < $limit,
        ];
    }

    private function usedToday(?User $user, string $ip): int
    {
        $query = Simulation::query()->whereDate('created_at', today());

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('ip_address', $ip);
        }

        return $query->count();
    }
}
