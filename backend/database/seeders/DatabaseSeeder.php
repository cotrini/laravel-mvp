<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Property\Models\Property;
use App\Modules\Media\Models\Media;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Property::factory(10)->create()->each(function ($property) {
            Media::factory(3)->create([
                'property_id' => $property->id,
                'mediable_id' => $property->id,
                'mediable_type' => \App\Modules\Property\Models\Property::class,
            ]);
        });
    }
}
