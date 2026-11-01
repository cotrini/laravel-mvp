<?php

namespace Tests\Feature;

use App\Modules\Credito\Models\BankInterestRate;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BankInterestRateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_administrators_can_manage_bank_interest_rates(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.bank-interest-rates.index'))
            ->assertOk()
            ->assertSee('Intereses bancos')
            ->assertSee('Bancolombia')
            ->assertSee('Scotiabank Colpatria');

        $this->actingAs($user)
            ->get(route('admin.bank-interest-rates.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_and_clear_a_bank_rate(): void
    {
        $admin = $this->createAdmin();
        $bank = BankInterestRate::query()->where('slug', 'bancolombia')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.bank-interest-rates.update', $bank), [
                'annual_effective_rate' => '14.75',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bank_interest_rates', [
            'id' => $bank->id,
            'annual_effective_rate' => 14.75,
        ]);

        $this->put(route('admin.bank-interest-rates.update', $bank), [
            'annual_effective_rate' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('bank_interest_rates', [
            'id' => $bank->id,
            'annual_effective_rate' => null,
        ]);
    }

    public function test_simulator_uses_a_configured_bank_rate_and_rejects_an_unconfigured_rate(): void
    {
        $bank = BankInterestRate::query()->where('slug', 'bancolombia')->firstOrFail();
        $bank->update(['annual_effective_rate' => 12]);

        $this->post(route('credit-simulator.calculate'), [
            'loan_amount' => 100000000,
            'bank_id' => $bank->id,
            'term_unit' => 'months',
            'term_value' => 12,
        ])
            ->assertOk()
            ->assertSee('Tasa de Bancolombia: 12,00% E.A.')
            ->assertSee('$8.856.207 COP');

        $unconfiguredBank = BankInterestRate::query()->where('slug', 'davivienda')->firstOrFail();
        $this->from(route('credit-simulator.show'))
            ->post(route('credit-simulator.calculate'), [
                'loan_amount' => 100000000,
                'bank_id' => $unconfiguredBank->id,
                'term_unit' => 'months',
                'term_value' => 12,
            ])
            ->assertRedirect(route('credit-simulator.show'))
            ->assertSessionHasErrors('bank_id');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return $admin;
    }
}
