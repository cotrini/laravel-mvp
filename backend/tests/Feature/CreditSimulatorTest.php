<?php

namespace Tests\Feature;

use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreditSimulatorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_simulator_page_prefills_and_formats_a_property_price_without_showing_advisory(): void
    {
        $response = $this->get(route('credit-simulator.show', ['amount' => 250000000]));

        $response->assertOk()
            ->assertSee('Simulador de crédito de vivienda')
            ->assertSee('name="property_value"', false)
            ->assertSee('value="250.000.000"', false)
            ->assertSee('value="250000000"', false)
            ->assertSee('value="30"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="10"', false);
        $response->assertDontSee('Pedir asesoría financiera por WhatsApp');
    }

    public function test_property_page_preserves_the_property_when_opening_the_simulator(): void
    {
        $property = Property::factory()->create([
            'price' => 250000000,
        ]);

        $response = $this->get(route('properties.show', $property));

        $response->assertOk()
            ->assertSee('amount='.$property->price, false)
            ->assertSee('property_id='.$property->id, false);
    }

    public function test_zero_rate_calculation_returns_equal_monthly_installments(): void
    {
        $response = $this->post(route('credit-simulator.calculate'), [
            'property_value' => 15000000,
            'initial_payment_type' => 'percentage',
            'initial_payment_percentage' => 20,
            'annual_effective_rate' => 0,
            'term_unit' => 'months',
            'term_value' => 12,
        ]);

        $response->assertOk()
            ->assertSee('$1.000.000 COP')
            ->assertSee('$12.000.000 COP')
            ->assertSee('$3.000.000 COP')
            ->assertSee('Pedir asesoría financiera por WhatsApp')
            ->assertSee('Tabla de amortización mensual')
            ->assertSee('value="months"', false);
    }

    #[DataProvider('downPaymentPercentages')]
    public function test_selected_down_payment_percentage_is_applied_to_the_property_value(
        int $percentage,
        int $downPayment,
        int $financedAmount,
    ): void {
        $response = $this->post(route('credit-simulator.calculate'), [
            'property_value' => 100000000,
            'initial_payment_type' => 'percentage',
            'initial_payment_percentage' => $percentage,
            'annual_effective_rate' => 0,
            'term_unit' => 'months',
            'term_value' => 12,
        ]);

        $response->assertOk()
            ->assertSee('$'.number_format($downPayment, 0, ',', '.').' COP')
            ->assertSee('$'.number_format($financedAmount, 0, ',', '.').' COP');
    }

    public function test_effective_annual_rate_is_converted_to_a_monthly_fixed_payment(): void
    {
        $response = $this->post(route('credit-simulator.calculate'), [
            'property_value' => 125000000,
            'initial_payment_type' => 'percentage',
            'initial_payment_percentage' => 20,
            'annual_effective_rate' => 12,
            'term_unit' => 'months',
            'term_value' => 12,
        ]);

        $response->assertOk()
            ->assertSee('$8.856.207 COP')
            ->assertSee('Intereses estimados');
    }

    public function test_custom_down_payment_advisory_includes_customer_and_property_details(): void
    {
        $customer = User::factory()->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.test',
            'phone_number' => '+573001234567',
        ]);
        $property = Property::factory()->create([
            'title' => 'Apartamento en Chapinero',
            'price' => 100000000,
            'neighborhood' => 'Chapinero',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
        ]);

        $response = $this->actingAs($customer)->post(route('credit-simulator.calculate'), [
            'property_id' => $property->id,
            'property_value' => 100000000,
            'initial_payment_type' => 'amount',
            'initial_payment_amount' => 30000000,
            'annual_effective_rate' => 0,
            'term_unit' => 'months',
            'term_value' => 12,
        ]);

        $response->assertOk()
            ->assertSee('$30.000.000 COP')
            ->assertSee('$70.000.000 COP')
            ->assertSee($property->title)
            ->assertSee(route('properties.show', $property), false)
            ->assertSee('value="Ana Pérez"', false)
            ->assertSee('value="ana@example.test"', false)
            ->assertSee('value="+573001234567"', false)
            ->assertSee('Pedir asesoría financiera por WhatsApp');
    }

    public function test_custom_down_payment_cannot_equal_or_exceed_the_property_value(): void
    {
        $response = $this->from(route('credit-simulator.show'))
            ->post(route('credit-simulator.calculate'), [
                'property_value' => 100000000,
                'initial_payment_type' => 'amount',
                'initial_payment_amount' => 100000000,
                'annual_effective_rate' => 0,
                'term_unit' => 'years',
                'term_value' => 20,
            ]);

        $response->assertRedirect(route('credit-simulator.show'))
            ->assertSessionHasErrors(['initial_payment_amount']);
    }

    public function test_invalid_rate_and_monthly_term_are_rejected(): void
    {
        $response = $this->from(route('credit-simulator.show'))
            ->post(route('credit-simulator.calculate'), [
                'property_value' => 100000000,
                'initial_payment_type' => 'percentage',
                'initial_payment_percentage' => 20,
                'annual_effective_rate' => 100.01,
                'term_unit' => 'months',
                'term_value' => 361,
            ]);

        $response->assertRedirect(route('credit-simulator.show'))
            ->assertSessionHasErrors(['annual_effective_rate', 'term_value']);
    }

    public static function downPaymentPercentages(): array
    {
        return [
            '10 percent' => [10, 10000000, 90000000],
            '30 percent' => [30, 30000000, 70000000],
        ];
    }
}
