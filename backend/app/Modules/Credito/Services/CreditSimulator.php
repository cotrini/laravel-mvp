<?php

namespace App\Modules\Credito\Services;

class CreditSimulator
{
    /**
     * @return array{
     *     monthly_payment: int,
     *     total_payment: int,
     *     total_interest: int,
     *     schedule: array<int, array{month: int, payment: int, principal: int, interest: int, balance: int}>
     * }
     */
    public function calculate(int $loanAmount, float $annualEffectiveRate, int $termMonths): array
    {
        $monthlyRate = (1 + $annualEffectiveRate) ** (1 / 12) - 1;
        $monthlyPayment = $monthlyRate === 0.0
            ? (int) round($loanAmount / $termMonths)
            : (int) round(
                $loanAmount * $monthlyRate / (1 - (1 + $monthlyRate) ** -$termMonths),
            );
        $balance = $loanAmount;
        $totalPayment = 0;
        $totalInterest = 0;
        $schedule = [];

        for ($month = 1; $month <= $termMonths; $month++) {
            $interest = (int) round($balance * $monthlyRate);
            $payment = min($monthlyPayment, $balance + $interest);
            $principal = $payment - $interest;
            $balance = max(0, $balance - $principal);
            $totalPayment += $payment;
            $totalInterest += $interest;
            $schedule[] = [
                'month' => $month,
                'payment' => $payment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => $balance,
            ];
        }

        return [
            'monthly_payment' => $monthlyPayment,
            'total_payment' => $totalPayment,
            'total_interest' => $totalInterest,
            'schedule' => $schedule,
        ];
    }
}
