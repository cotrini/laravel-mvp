<?php

namespace App\Modules\Media\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'mediable_type' => Property::class,
            'mediable_id' => Property::factory(),
            'type' => 'image',
            'url_or_path' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
            'title' => $this->faker->sentence(3),
            'order' => 0,
            'is_primary' => false,
            'meta' => null,
        ];
    }
}