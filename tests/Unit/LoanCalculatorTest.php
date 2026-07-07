<?php

namespace Tests\Unit;

use App\Services\LoanCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LoanCalculatorTest extends TestCase
{
    private LoanCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new LoanCalculator();
    }

    public function test_annuity_monthly_payment_is_constant_and_correct(): void
    {
        // 10 000 000 à 6 % sur 120 mois → M = C·i / (1 − (1+i)^−n) ≈ 111 020,25
        $result = $this->calculator->amortization([
            'amount' => 10000000,
            'annual_rate' => 6,
            'duration_months' => 120,
            'method' => 'annuity',
        ]);

        $this->assertEqualsWithDelta(111020.25, $result['summary']['monthly_payment'], 1);
        $this->assertCount(120, $result['schedule']);

        // Toutes les mensualités identiques (à l'arrondi près sur la dernière)
        $payments = array_column($result['schedule'], 'payment');
        foreach (array_slice($payments, 0, -1) as $payment) {
            $this->assertEqualsWithDelta($payments[0], $payment, 0.02);
        }

        // Solde final nul et somme du capital = montant emprunté
        $this->assertSame(0.0, end($result['schedule'])['balance']);
        $this->assertEqualsWithDelta(10000000, array_sum(array_column($result['schedule'], 'principal')), 0.5);
    }

    public function test_annuity_with_zero_rate_splits_capital_evenly(): void
    {
        $result = $this->calculator->amortization([
            'amount' => 1200000,
            'annual_rate' => 0,
            'duration_months' => 12,
            'method' => 'annuity',
        ]);

        $this->assertEqualsWithDelta(100000, $result['summary']['monthly_payment'], 0.01);
        $this->assertSame(0.0, $result['summary']['total_interest']);
    }

    public function test_constant_capital_has_fixed_principal_and_decreasing_payments(): void
    {
        $result = $this->calculator->amortization([
            'amount' => 1200000,
            'annual_rate' => 12,
            'duration_months' => 12,
            'method' => 'constant_capital',
        ]);

        $schedule = $result['schedule'];
        $this->assertEqualsWithDelta(100000, $schedule[0]['principal'], 0.01);
        $this->assertEqualsWithDelta(12000, $schedule[0]['interest'], 0.01); // 1 200 000 × 1 %
        $this->assertGreaterThan($schedule[5]['payment'], $schedule[0]['payment']);
        $this->assertSame(0.0, end($schedule)['balance']);
    }

    public function test_in_fine_pays_interest_only_then_capital_at_end(): void
    {
        $result = $this->calculator->amortization([
            'amount' => 5000000,
            'annual_rate' => 6,
            'duration_months' => 24,
            'method' => 'in_fine',
        ]);

        $schedule = $result['schedule'];
        $this->assertSame(0.0, $schedule[0]['principal']);
        $this->assertEqualsWithDelta(25000, $schedule[0]['interest'], 0.01); // 5 000 000 × 0,5 %
        $this->assertEqualsWithDelta(5000000, end($schedule)['principal'], 0.01);
        $this->assertEqualsWithDelta(25000 * 24, $result['summary']['total_interest'], 1);
    }

    public function test_insurance_is_added_to_each_payment(): void
    {
        // 0,36 % annuel sur 10 000 000 → 3 000 / mois
        $result = $this->calculator->amortization([
            'amount' => 10000000,
            'annual_rate' => 6,
            'duration_months' => 120,
            'method' => 'annuity',
            'insurance_rate' => 0.36,
        ]);

        $this->assertEqualsWithDelta(3000, $result['schedule'][0]['insurance'], 0.01);
        $this->assertEqualsWithDelta(3000 * 120, $result['summary']['total_insurance'], 1);
        $this->assertEqualsWithDelta(
            $result['summary']['total_interest'] + $result['summary']['total_insurance'],
            $result['summary']['total_cost'],
            0.01
        );
    }

    public function test_total_paid_is_consistent(): void
    {
        $result = $this->calculator->amortization([
            'amount' => 2500000,
            'annual_rate' => 8.5,
            'duration_months' => 60,
            'method' => 'annuity',
        ]);

        $this->assertEqualsWithDelta(
            2500000 + $result['summary']['total_interest'],
            $result['summary']['total_paid'],
            0.5
        );
    }

    public function test_borrowing_capacity_uses_40_percent_debt_ratio(): void
    {
        $result = $this->calculator->borrowingCapacity([
            'monthly_income' => 500000,
            'monthly_charges' => 100000,
            'annual_rate' => 6,
            'duration_months' => 120,
        ]);

        // Mensualité max = (500 000 − 100 000) × 40 % = 160 000
        $this->assertEqualsWithDelta(160000, $result['summary']['max_monthly_payment'], 0.01);
        // Capacité = M · (1 − (1+i)^−n) / i ≈ 14 411 752,53
        $this->assertEqualsWithDelta(14411752.53, $result['summary']['borrowing_capacity'], 1);
    }

    public function test_invalid_method_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->amortization([
            'amount' => 1000000,
            'annual_rate' => 5,
            'duration_months' => 12,
            'method' => 'balloon',
        ]);
    }
}
