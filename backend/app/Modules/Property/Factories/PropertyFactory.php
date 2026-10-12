<?php

namespace App\Modules\Property\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User; // Asegúrate de tener usuarios para asociar el user_id

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            // Asigna un usuario existente o crea uno al vuelo si no hay
            'user_id' => User::factory(), 
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->numberBetween(150000000, 1200000000), // Precios en COP
            'property_type' => $this->faker->randomElement(['Apartamento', 'Casa', 'Lote', 'Oficina', 'Penthouse']),
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => $this->faker->randomElement(['Chipre', 'Milán', 'El Cable', 'Palermo', 'La Carola']),
        ];
    }
}