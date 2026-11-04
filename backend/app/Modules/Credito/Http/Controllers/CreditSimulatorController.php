<?php

namespace App\Modules\Credito\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Credito\Models\BankInterestRate;
use App\Modules\Credito\Services\CreditSimulator;
use App\Modules\Property\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreditSimulatorController extends Controller
{
    public function show(Request $request): View
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'between:1,1000000000000'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);
        $property = $this->findProperty($validated['property_id'] ?? null);
        $propertyValue = old('property_value', $property?->price ?? $validated['amount'] ?? '');
        $initialPaymentType = old('initial_payment_type', 'percentage');
        $initialPaymentPercentage = (int) old('initial_payment_percentage', 20);
        $initialPaymentAmount = (int) old('initial_payment_amount', 0);
        $initialPayment = $propertyValue === ''
            ? 0
            : ($initialPaymentType === 'amount'
                ? $initialPaymentAmount
                : (int) round((int) $propertyValue * $initialPaymentPercentage / 100));

        return view('credito::simulator', [
            'property' => $property,
            'propertyUrl' => $property === null ? null : route('properties.show', $property),
            'propertyValue' => $propertyValue,
            'initialPaymentType' => $initialPaymentType,
            'initialPaymentPercentage' => $initialPaymentPercentage,
            'initialPaymentAmount' => $initialPaymentAmount,
            'initialPayment' => $initialPayment,
            'loanAmount' => $propertyValue === '' ? '' : max(0, (int) $propertyValue - $initialPayment),
            'interestRate' => old('annual_effective_rate', ''),
            'termUnit' => old('term_unit', 'years'),
            'termValue' => old('term_value', 20),
            'simulation' => null,
            'termMonths' => null,
            'banks' => BankInterestRate::query()->orderBy('id')->get(),
            'selectedBank' => null,
        ]);
    }

    public function calculate(Request $request, CreditSimulator $creditSimulator): View
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
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
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
        $initialPaymentPercentage = (int) ($validated['initial_payment_percentage'] ?? 20);
        $initialPaymentAmount = $hasPropertyValue
            ? (int) ($validated['initial_payment_amount'] ?? 0)
            : 0;
        $initialPayment = match ($initialPaymentType) {
            'amount' => $initialPaymentAmount,
            default => (int) round($propertyValue * $initialPaymentPercentage / 100),
        };
        $loanAmount = $propertyValue - $initialPayment;

        if ($loanAmount < 1) {
            throw ValidationException::withMessages([
                $initialPaymentType === 'amount' ? 'initial_payment_amount' : 'initial_payment_percentage' => 'La cuota inicial debe ser menor al valor del inmueble.',
            ]);
        }

        $termMonths = $validated['term_unit'] === 'years'
            ? $validated['term_value'] * 12
            : $validated['term_value'];
        $property = $this->findProperty($validated['property_id'] ?? null);
        $selectedBank = isset($validated['bank_id'])
            ? BankInterestRate::query()->findOrFail($validated['bank_id'])
            : null;

        if ($selectedBank !== null && $selectedBank->annual_effective_rate === null) {
            throw ValidationException::withMessages([
                'bank_id' => 'Este banco todavía no tiene una tasa configurada.',
            ]);
        }

        $effectiveRate = $selectedBank?->annual_effective_rate
            ?? $validated['annual_effective_rate'];

        return view('credito::simulator', [
            'property' => $property,
            'propertyUrl' => $property === null ? null : route('properties.show', $property),
            'propertyValue' => $propertyValue,
            'initialPaymentType' => $initialPaymentType,
            'initialPaymentPercentage' => $initialPaymentPercentage,
            'initialPaymentAmount' => $initialPaymentAmount,
            'initialPayment' => $initialPayment,
            'loanAmount' => $loanAmount,
            'interestRate' => $effectiveRate,
            'termUnit' => $validated['term_unit'],
            'termValue' => $validated['term_value'],
            'termMonths' => $termMonths,
            'banks' => BankInterestRate::query()->orderBy('id')->get(),
            'selectedBank' => $selectedBank,
            'simulation' => $creditSimulator->calculate(
                $loanAmount,
                (float) $effectiveRate / 100,
                $termMonths,
            ),
        ]);
    }

    private function findProperty(?int $propertyId): ?Property
    {
        if ($propertyId === null) {
            return null;
        }

        $property = Property::query()->findOrFail($propertyId);

        abort_if(
            ! $property->is_active && ! auth()->user()?->canAccessProperty($property),
            404,
        );

        return $property;
    }
}
