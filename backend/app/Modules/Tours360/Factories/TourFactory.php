<?php

namespace App\Modules\Tours360\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Tours360\Models\Tour;

class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'cover_url' => $this->faker->imageUrl(1200, 800),
            'location' => $this->faker->city(),
            'is_published' => true,
        ];
    }
}