<?php

namespace App\Modules\Payment\Factories;

use App\Modules\Payment\Models\PaymentOrder;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOrder>
 */
class PaymentOrderFactory extends Factory
{
    protected $model = PaymentOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => PaymentOrder::PURPOSE_EXTRA_PUBLICATION,
            'reference' => PaymentOrder::generateReference(),
            'amount_in_cents' => (int) config('services.wompi.publication_price_in_cents'),
            'currency' => 'COP',
            'status' => PaymentOrder::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentOrder::STATUS_APPROVED,
            'wompi_status' => 'APPROVED',
            'paid_at' => now(),
        ]);
    }
}
