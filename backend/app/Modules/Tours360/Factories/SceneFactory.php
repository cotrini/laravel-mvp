<?php

namespace App\Modules\Tours360\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Tours360\Models\Scene;

class SceneFactory extends Factory
{
    protected $model = Scene::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'title' => $this->faker->words(3, true),
            'caption' => $this->faker->sentence(),
            'image_url' => $this->faker->imageUrl(1800, 900),
            'sort_order' => 1,
        ];
    }
}