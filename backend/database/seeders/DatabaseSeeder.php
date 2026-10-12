<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Property\Models\Property;
use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User as UserModel;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // Property::factory(10)->create()->each(function ($property) {
        //     Media::factory(3)->create([
        //         'property_id' => $property->id,
        //         'mediable_id' => $property->id,
        //         'mediable_type' => \App\Modules\Property\Models\Property::class,
        //     ]);
        // });

        //for create 10 users, each with 2 properties, and each property with 3 media items
        userModel::factory(10)->create()->each(function ($user) {
            Property::factory(2)->create([
                'user_id' => $user->id,
            ])->each(function ($property) {
                Media::factory(3)->create([
                    'property_id' => $property->id,
                    'mediable_id' => $property->id,
                    'mediable_type' => \App\Modules\Property\Models\Property::class,
                ]);
            });
        });
        //for create a custome user with 2 properties, and each property with 3 media items
        userModel::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            ])->each(function ($user) {
                Property::factory(2)->create([
                    'user_id' => $user->id,
                ])->each(function ($property) {
                    Media::factory(3)->create([
                        'property_id' => $property->id,
                        'mediable_id' => $property->id,
                        'mediable_type' => \App\Modules\Property\Models\Property::class,
                    ]);
                });
            });
        
    }
}
