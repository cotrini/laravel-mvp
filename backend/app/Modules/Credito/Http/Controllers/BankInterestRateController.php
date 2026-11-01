<?php

namespace App\Modules\Credito\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Credito\Models\BankInterestRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankInterestRateController extends Controller
{
    public function index(): View
    {
        $banks = BankInterestRate::query()->orderBy('id')->get();

        return view('credito::admin.banks', compact('banks'));
    }

    public function update(Request $request, BankInterestRate $bank): RedirectResponse
    {
        $validated = $request->validate([
            'annual_effective_rate' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $bank->update($validated);

        return back()->with('success', 'La tasa del banco fue actualizada.');
    }
}
