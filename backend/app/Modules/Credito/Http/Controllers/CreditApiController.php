<?php

namespace App\Modules\Credito\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Credito\Models\BankInterestRate;
use App\Modules\Credito\Services\CreditSimulator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreditApiController extends Controller
{
    public function banks(): JsonResponse
    {
        $banks = BankInterestRate::query()
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'logo_label', 'annual_effective_rate']);

        return response()->json(['data' => $banks]);
    }

    public function simulate(Request $request, CreditSimulator $creditSimulator): JsonResponse
    {
        $validated = $request->validate([
            'property_value' => [
                'nullable',
                'required_without:loan_amount',
                'integer',
                'between:1,1000000000000',
            ],
            'loan_amount' => [
                'nullable',
                'required_without:property_value',
                'integer',
                'between:1,1000000000000',
            ],
            'initial_payment_type' => ['nullable', Rule::in(['percentage', 'amount'])],
            'initial_payment_percentage' => [
                'nullable',
                'required_if:initial_payment_type,percentage',
                'integer',
                Rule::in([10, 20, 30]),
            ],
            'initial_payment_amount' => [
                'nullable',
                'required_if:initial_payment_type,amount',
                'integer',
                'between:0,1000000000000',
            ],
            'annual_effective_rate' => ['nullable', 'required_without:bank_id', 'numeric', 'between:0,100'],
            'bank_id' => ['nullable', 'integer', 'exists:bank_interest_rates,id'],
            'term_unit' => ['required', 'in:years,months'],
            'term_value' => [
                'required',
                'integer',
                Rule::when($request->input('term_unit') === 'years', ['between:1,30']),
                Rule::when($request->input('term_unit') === 'months', ['between:12,360']),
            ],
        ]);

        $hasPropertyValue = isset($validated['property_value']);
        $propertyValue = (int) ($validated['property_value'] ?? $validated['loan_amount']);
        $initialPaymentType = $hasPropertyValue
            ? ($validated['initial_payment_type'] ?? 'percentage')
            : 'amount';
        $initialPayment = $hasPropertyValue
            ? match ($initialPaymentType) {
                'amount' => (int) ($validated['initial_payment_amount'] ?? 0),
                default => (int) round($propertyValue * (int) ($validated['initial_payment_percentage'] ?? 20) / 100),
            }
        : 0;
        $loanAmount = $propertyValue - $initialPayment;

        if ($loanAmount < 1) {
            throw ValidationException::withMessages([
                $initialPaymentType === 'amount' ? 'initial_payment_amount' : 'initial_payment_percentage' => 'La cuota inicial debe ser menor al valor del inmueble.',
            ]);
        }

        $bank = isset($validated['bank_id'])
            ? BankInterestRate::query()->findOrFail($validated['bank_id'])
            : null;

        if ($bank !== null && $bank->annual_effective_rate === null) {
            throw ValidationException::withMessages([
                'bank_id' => 'Este banco todavía no tiene una tasa configurada.',
            ]);
        }

        $effectiveRate = (float) ($bank?->annual_effective_rate ?? $validated['annual_effective_rate']);
        $termMonths = $validated['term_unit'] === 'years'
            ? (int) $validated['term_value'] * 12
            : (int) $validated['term_value'];

        return response()->json([
            'data' => [
                'property_value' => $propertyValue,
                'initial_payment' => $initialPayment,
                'loan_amount' => $loanAmount,
                'annual_effective_rate' => $effectiveRate,
                'bank' => $bank?->only(['id', 'name', 'slug']),
                'term_months' => $termMonths,
                'simulation' => $creditSimulator->calculate($loanAmount, $effectiveRate / 100, $termMonths),
            ],
        ]);
    }
}
