<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Property\Models\Property;
use App\Modules\Media\Models\Media;
use App\Modules\User\Models\User as UserModel;
use App\Modules\Tours360\Models\Tour;
use App\Modules\Tours360\Models\Scene;
use App\Modules\Tours360\Models\Hotspot;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
                //for create 5 tours, each with 3 scenes, and each scene with 2 hotspots
        $pexels = function (int $id, int $w = 1800) {
            return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";
        };

        // --- Tour 1: Cumbres Salvajes ---
        $mountains = Tour::factory()->create([
            'title'         => 'Cumbres Salvajes',
            'slug'          => 'cumbres-salvajes',
            'description'   => 'Recorre las cumbres más impresionantes bajo cielos dramáticos. Un viaje entre picos nevados y valles profundos.',
            'cover_url'     => $pexels(141783, 1200),
            'location'      => 'Cordillera del Norte',
            'is_published'  => true,
        ]);

        $m1 = $mountains->scenes()->create(['title' => 'Panorama del Valle', 'caption' => 'Vista del valle al amanecer con nubes dramáticas.', 'image_url' => $pexels(141783), 'sort_order' => 1]);
        $m2 = $mountains->scenes()->create(['title' => 'Cima Nevada', 'caption' => 'La cima con vistas de 360 grados sobre la cordillera.', 'image_url' => $pexels(1303431), 'sort_order' => 2]);
        $m3 = $mountains->scenes()->create(['title' => 'Bosque de Pinos', 'caption' => 'Denso bosque de pinos al pie de las montañas.', 'image_url' => $pexels(33004623), 'sort_order' => 3]);

        Hotspot::factory()->create(['scene_id' => $m1->id, 'target_scene_id' => $m2->id, 'label' => 'Subir al pico', 'yaw' => 0.4, 'pitch' => -0.1]);
        Hotspot::factory()->create(['scene_id' => $m1->id, 'target_scene_id' => $m3->id, 'label' => 'Bosque cercano', 'yaw' => -1.2, 'pitch' => 0.05]);
        Hotspot::factory()->create(['scene_id' => $m2->id, 'target_scene_id' => $m1->id, 'label' => 'Volver al valle', 'yaw' => 2.4, 'pitch' => 0.1]);
        Hotspot::factory()->create(['scene_id' => $m2->id, 'target_scene_id' => $m3->id, 'label' => 'Mirador del bosque', 'yaw' => -0.6, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $m3->id, 'target_scene_id' => $m1->id, 'label' => 'Salir al valle', 'yaw' => 1.5, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $m3->id, 'target_scene_id' => $m2->id, 'label' => 'Subir a la cima', 'yaw' => -1.5, 'pitch' => -0.1]);

        // --- Tour 2: Costa al Atardecer ---
        $coast = Tour::factory()->create([
            'title'         => 'Costa al Atardecer',
            'slug'          => 'costa-atardecer',
            'description'   => 'Sumérgete en la calma del océano mientras el sol desciende. Playas doradas y olas tranquilas te esperan.',
            'cover_url'     => $pexels(21820342, 1200),
            'location'      => 'Bahía Dorada',
            'is_published'  => true,
        ]);

        $c1 = $coast->scenes()->create(['title' => 'Playa Áurea', 'caption' => 'Arena dorada bajo un cielo al atardecer.', 'image_url' => $pexels(21820342), 'sort_order' => 1]);
        $c2 = $coast->scenes()->create(['title' => 'Acantilado Tormentoso', 'caption' => 'Nubes dramáticas sobre el océano desde el acantilado.', 'image_url' => $pexels(20390932), 'sort_order' => 2]);
        $c3 = $coast->scenes()->create(['title' => 'Oleaje Tranquilo', 'caption' => 'Olas suaves bajo un cielo vibrante.', 'image_url' => $pexels(321474), 'sort_order' => 3]);

        Hotspot::factory()->create(['scene_id' => $c1->id, 'target_scene_id' => $c2->id, 'label' => 'Acantilado', 'yaw' => 1.1, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $c1->id, 'target_scene_id' => $c3->id, 'label' => 'Rocas y olas', 'yaw' => -1.0, 'pitch' => 0.05]);
        Hotspot::factory()->create(['scene_id' => $c2->id, 'target_scene_id' => $c1->id, 'label' => 'Volver a la playa', 'yaw' => 2.8, 'pitch' => 0.1]);
        Hotspot::factory()->create(['scene_id' => $c2->id, 'target_scene_id' => $c3->id, 'label' => 'Rocas y olas', 'yaw' => -0.8, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $c3->id, 'target_scene_id' => $c1->id, 'label' => 'Playa áurea', 'yaw' => 0.6, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $c3->id, 'target_scene_id' => $c2->id, 'label' => 'Acantilado', 'yaw' => -1.4, 'pitch' => -0.05]);

        // --- Tour 3: Sendero del Bosque ---
        $forest = Tour::factory()->create([
            'title'         => 'Sendero del Bosque',
            'slug'          => 'sendero-bosque',
            'description'   => 'Camina entre árboles centenarios y descubre la paz del bosque. Cada sendero revela un nuevo rincón natural.',
            'cover_url'     => $pexels(6892865, 1200),
            'location'      => 'Bosque de Opawskie',
            'is_published'  => true,
        ]);

        $f1 = $forest->scenes()->create(['title' => 'Camino del Valle', 'caption' => 'Sendero con vista al valle bajo un cielo de verano.', 'image_url' => $pexels(6892865), 'sort_order' => 1]);
        $f2 = $forest->scenes()->create(['title' => 'Sendero de Montaña', 'caption' => 'Tranquilo sendero entre los árboles.', 'image_url' => $pexels(37596998), 'sort_order' => 2]);
        $f3 = $forest->scenes()->create(['title' => 'Camino Rural', 'caption' => 'Camino primaveral bordeado de abetos.', 'image_url' => $pexels(38350789), 'sort_order' => 3]);

        Hotspot::factory()->create(['scene_id' => $f1->id, 'target_scene_id' => $f2->id, 'label' => 'Sendero profundo', 'yaw' => 0.3, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $f1->id, 'target_scene_id' => $f3->id, 'label' => 'Camino rural', 'yaw' => -1.6, 'pitch' => 0.05]);
        Hotspot::factory()->create(['scene_id' => $f2->id, 'target_scene_id' => $f1->id, 'label' => 'Volver al valle', 'yaw' => 2.6, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $f2->id, 'target_scene_id' => $f3->id, 'label' => 'Camino rural', 'yaw' => -0.7, 'pitch' => 0.05]);
        Hotspot::factory()->create(['scene_id' => $f3->id, 'target_scene_id' => $f1->id, 'label' => 'Camino del valle', 'yaw' => 1.2, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $f3->id, 'target_scene_id' => $f2->id, 'label' => 'Sendero profundo', 'yaw' => -1.2, 'pitch' => -0.05]);

        // --- Tour 4: Ciudad Vibrante ---
        $city = Tour::factory()->create([
            'title'         => 'Ciudad Vibrante',
            'slug'          => 'ciudad-vibrante',
            'description'   => 'Explora el corazón urbano: calles históricas, rascacielos y luces nocturnas en un recorrido inmersivo.',
            'cover_url'     => $pexels(40271942, 1200),
            'location'      => 'Centro Metropolitano',
            'is_published'  => true,
        ]);

        $ct1 = $city->scenes()->create(['title' => 'Calle Europea', 'caption' => 'Arquitectura clásica en una calle pintoresca.', 'image_url' => $pexels(40271942), 'sort_order' => 1]);
        $ct2 = $city->scenes()->create(['title' => 'Vista Aérea', 'caption' => 'Rascacielos bajo un cielo dramático.', 'image_url' => $pexels(30893717), 'sort_order' => 2]);
        $ct3 = $city->scenes()->create(['title' => 'Ciudad Verde', 'caption' => 'Mezcla de arquitectura moderna y vegetación.', 'image_url' => $pexels(39542276), 'sort_order' => 3]);
        $ct4 = $city->scenes()->create(['title' => 'Noche Urbana', 'caption' => 'Calles iluminadas con luces vibrantes.', 'image_url' => $pexels(40319415), 'sort_order' => 4]);

        Hotspot::factory()->create(['scene_id' => $ct1->id, 'target_scene_id' => $ct2->id, 'label' => 'Vista aérea', 'yaw' => 0.5, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $ct1->id, 'target_scene_id' => $ct3->id, 'label' => 'Ciudad verde', 'yaw' => -1.3, 'pitch' => 0.05]);
        Hotspot::factory()->create(['scene_id' => $ct1->id, 'target_scene_id' => $ct4->id, 'label' => 'Noche urbana', 'yaw' => 2.2, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $ct2->id, 'target_scene_id' => $ct1->id, 'label' => 'Calle europea', 'yaw' => 2.9, 'pitch' => 0.1]);
        Hotspot::factory()->create(['scene_id' => $ct2->id, 'target_scene_id' => $ct3->id, 'label' => 'Ciudad verde', 'yaw' => -0.6, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $ct3->id, 'target_scene_id' => $ct1->id, 'label' => 'Calle europea', 'yaw' => 1.0, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $ct3->id, 'target_scene_id' => $ct4->id, 'label' => 'Noche urbana', 'yaw' => -1.2, 'pitch' => -0.05]);
        Hotspot::factory()->create(['scene_id' => $ct4->id, 'target_scene_id' => $ct1->id, 'label' => 'Calle europea', 'yaw' => 0.8, 'pitch' => 0.0]);
        Hotspot::factory()->create(['scene_id' => $ct4->id, 'target_scene_id' => $ct2->id, 'label' => 'Vista aérea', 'yaw' => -1.4, 'pitch' => -0.05]);

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
        //for create a custome user with 2 properties, and each property with 3 media items with a random tour_id from the tours table for each proerty
        $tourIds = \App\Modules\Tours360\Models\Tour::pluck('id');

        userModel::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            ])->each(function ($user) use ($tourIds) {
                Property::factory(2)->create([
                    'user_id' => $user->id,
                    'tour_id' => $tourIds->random(),
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
