<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_interest_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_label', 8);
            $table->decimal('annual_effective_rate', 5, 2)->nullable();
            $table->timestamps();
        });

        DB::table('bank_interest_rates')->insert([
            ['name' => 'Bancolombia', 'slug' => 'bancolombia', 'logo_label' => 'B', 'annual_effective_rate' => null],
            ['name' => 'Davivienda', 'slug' => 'davivienda', 'logo_label' => 'D', 'annual_effective_rate' => null],
            ['name' => 'Banco de Bogotá', 'slug' => 'banco-de-bogota', 'logo_label' => 'BdB', 'annual_effective_rate' => null],
            ['name' => 'BBVA', 'slug' => 'bbva', 'logo_label' => 'BBVA', 'annual_effective_rate' => null],
            ['name' => 'Scotiabank Colpatria', 'slug' => 'scotiabank-colpatria', 'logo_label' => 'SC', 'annual_effective_rate' => null],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_interest_rates');
    }
};
