<?php

namespace App\Modules\User\Factories;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone_number' => '+57'.$this->faker->unique()->numerify('3#########'),
            'password' => Hash::make('password'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
            'email_verification_required' => true,
        ]);
    }
}
