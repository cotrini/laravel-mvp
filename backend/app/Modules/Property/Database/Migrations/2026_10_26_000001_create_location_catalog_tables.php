<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPARTMENTS = [
        'Amazonas', 'Antioquia', 'Arauca', 'Archipiélago de San Andrés, Providencia y Santa Catalina',
        'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Casanare',
        'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare',
        'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander',
        'Putumayo', 'Quindío', 'Risaralda', 'Santander', 'Sucre', 'Tolima',
        'Valle del Cauca', 'Vaupés', 'Vichada',
    ];

    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['city_id', 'name']);
        });

        $now = now();
        DB::table('departments')->insert(array_map(
            static fn (string $name): array => ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
            self::DEPARTMENTS,
        ));

        foreach (DB::table('properties')->select('department', 'city', 'neighborhood')->distinct()->get() as $property) {
            $departmentId = DB::table('departments')->where('name', $property->department)->value('id');

            if ($departmentId === null) {
                $departmentId = DB::table('departments')->insertGetId([
                    'name' => $property->department,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $cityId = DB::table('cities')->where('department_id', $departmentId)->where('name', $property->city)->value('id');

            if ($cityId === null) {
                $cityId = DB::table('cities')->insertGetId([
                    'department_id' => $departmentId,
                    'name' => $property->city,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($property->neighborhood !== null && $property->neighborhood !== '') {
                DB::table('neighborhoods')->insertOrIgnore([
                    'city_id' => $cityId,
                    'name' => $property->neighborhood,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhoods');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('departments');
    }
};
