<?php

namespace App\Modules\Tours360\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Tours360\Models\Hotspot;
use App\Modules\Tours360\Models\Scene;

class HotspotFactory extends Factory
{
    protected $model = Hotspot::class;

    public function definition(): array
    {
        return [
            'scene_id' => Scene::factory(),
            'target_scene_id' => Scene::factory(), // Por defecto creará otra escena, pero la sobreescribiremos en el seeder si es necesario
            'label' => $this->faker->word(),
            'yaw' => $this->faker->randomFloat(2, -3.14, 3.14),
            'pitch' => $this->faker->randomFloat(2, -1.57, 1.57),
        ];
    }
}