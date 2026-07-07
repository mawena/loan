<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Moteur de calcul des prêts — source de vérité unique (affichage + exports).
 *
 * Méthodes d'amortissement supportées :
 *  - annuity          : annuités constantes (mensualité fixe)
 *  - constant_capital : amortissement constant (part de capital fixe)
 *  - in_fine          : intérêts seuls chaque mois, capital remboursé à la fin
 */
class LoanCalculator
{
    public const METHODS = ['annuity', 'constant_capital', 'in_fine'];

    /**
     * Calcule un tableau d'amortissement complet.
     *
     * @param array $params ['amount', 'annual_rate', 'duration_months', 'method',
     *                       'insurance_rate' (annuel, optionnel), 'start_date' (optionnel)]
     * @return array ['params' => ..., 'summary' => ..., 'schedule' => ...]
     */
    public function amortization(array $params): array
    {
        $amount = (float) $params['amount'];
        $annualRate = (float) $params['annual_rate'];
        $months = (int) $params['duration_months'];
        $method = $params['method'] ?? 'annuity';
        $insuranceRate = (float) ($params['insurance_rate'] ?? 0);
        $startDate = isset($params['start_date'])
            ? Carbon::parse($params['start_date'])->startOfMonth()
            : Carbon::now()->addMonth()->startOfMonth();

        if ($amount <= 0 || $months < 1) {
            throw new InvalidArgumentException('Montant ou durée invalide.');
        }
        if (!in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException("Méthode d'amortissement inconnue : $method");
        }

        $monthlyRate = $annualRate / 12 / 100;
        $monthlyInsurance = round($amount * $insuranceRate / 12 / 100, 2);

        $schedule = match ($method) {
            'annuity' => $this->annuitySchedule($amount, $monthlyRate, $months),
            'constant_capital' => $this->constantCapitalSchedule($amount, $monthlyRate, $months),
            'in_fine' => $this->inFineSchedule($amount, $monthlyRate, $months),
        };

        $totalInterest = 0.0;
        $totalInsurance = 0.0;
        foreach ($schedule as $i => &$row) {
            $date = $startDate->copy()->addMonths($i);
            $row['date'] = $date->format('Y-m-d');
            $row['insurance'] = $monthlyInsurance;
            $row['total'] = round($row['payment'] + $monthlyInsurance, 2);
            $totalInterest += $row['interest'];
            $totalInsurance += $monthlyInsurance;
        }
        unset($row);

        $totalPaid = round($amount + $totalInterest + $totalInsurance, 2);
        $first = $schedule[0];
        $last = $schedule[count($schedule) - 1];

        return [
            'params' => [
                'amount' => round($amount, 2),
                'annual_rate' => $annualRate,
                'duration_months' => $months,
                'method' => $method,
                'insurance_rate' => $insuranceRate,
                'start_date' => $startDate->format('Y-m-d'),
            ],
            'summary' => [
                'monthly_payment' => $first['total'],
                'first_payment' => $first['total'],
                'last_payment' => $last['total'],
                'total_principal' => round($amount, 2),
                'total_interest' => round($totalInterest, 2),
                'total_insurance' => round($totalInsurance, 2),
                'total_cost' => round($totalInterest + $totalInsurance, 2),
                'total_paid' => $totalPaid,
                'end_date' => $last['date'],
            ],
            'schedule' => $schedule,
        ];
    }

    /**
     * Capacité d'emprunt à partir des revenus/charges et du taux d'endettement max (40 %).
     */
    public function borrowingCapacity(array $params): array
    {
        $income = (float) $params['monthly_income'];
        $charges = (float) ($params['monthly_charges'] ?? 0);
        $annualRate = (float) $params['annual_rate'];
        $months = (int) $params['duration_months'];
        $debtRatio = (float) ($params['debt_ratio'] ?? 40);

        $maxPayment = max(0, ($income - $charges) * $debtRatio / 100);
        $monthlyRate = $annualRate / 12 / 100;

        $capacity = $monthlyRate > 0
            ? $maxPayment * (1 - (1 + $monthlyRate) ** -$months) / $monthlyRate
            : $maxPayment * $months;

        return [
            'params' => [
                'monthly_income' => $income,
                'monthly_charges' => $charges,
                'annual_rate' => $annualRate,
                'duration_months' => $months,
                'debt_ratio' => $debtRatio,
            ],
            'summary' => [
                'max_monthly_payment' => round($maxPayment, 2),
                'borrowing_capacity' => round($capacity, 2),
                'total_interest' => round($maxPayment * $months - $capacity, 2),
                'current_debt_ratio' => $income > 0 ? round($charges / $income * 100, 2) : null,
            ],
        ];
    }

    /**
     * Annuités constantes : M = C·i / (1 − (1+i)^−n).
     *
     * @return array<int, array{period:int, payment:float, principal:float, interest:float, balance:float}>
     */
    private function annuitySchedule(float $amount, float $rate, int $months): array
    {
        $payment = $rate > 0
            ? $amount * $rate / (1 - (1 + $rate) ** -$months)
            : $amount / $months;

        $schedule = [];
        $balance = $amount;

        for ($p = 1; $p <= $months; $p++) {
            $interest = round($balance * $rate, 2);
            if ($p < $months) {
                $principal = round($payment - $interest, 2);
            } else {
                // Dernière échéance : on solde exactement le capital restant
                $principal = round($balance, 2);
            }
            $balance = round($balance - $principal, 2);

            $schedule[] = [
                'period' => $p,
                'payment' => round($principal + $interest, 2),
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0.0, $balance),
            ];
        }

        return $schedule;
    }

    private function constantCapitalSchedule(float $amount, float $rate, int $months): array
    {
        $principalShare = $amount / $months;
        $schedule = [];
        $balance = $amount;

        for ($p = 1; $p <= $months; $p++) {
            $interest = round($balance * $rate, 2);
            $principal = $p < $months ? round($principalShare, 2) : round($balance, 2);
            $balance = round($balance - $principal, 2);

            $schedule[] = [
                'period' => $p,
                'payment' => round($principal + $interest, 2),
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0.0, $balance),
            ];
        }

        return $schedule;
    }

    private function inFineSchedule(float $amount, float $rate, int $months): array
    {
        $interest = round($amount * $rate, 2);
        $schedule = [];

        for ($p = 1; $p <= $months; $p++) {
            $principal = $p === $months ? round($amount, 2) : 0.0;

            $schedule[] = [
                'period' => $p,
                'payment' => round($principal + $interest, 2),
                'principal' => $principal,
                'interest' => $interest,
                'balance' => $p === $months ? 0.0 : round($amount, 2),
            ];
        }

        return $schedule;
    }
}
