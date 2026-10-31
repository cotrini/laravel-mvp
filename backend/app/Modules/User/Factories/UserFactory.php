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
            'phone_number' => '+57'.$this->faker->unique()->numerify('3#########'),
            'password' => Hash::make('password'),
        ];
    }
}
