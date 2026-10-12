<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            // user relationship
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');                    // Título del anuncio (ej: "Hermoso apartamento en Chipre")
            $table->text('description');                // Descripción detallada
            $table->decimal('price', 12, 2);            // Precio del inmueble (soporta cifras altas en pesos colombianos)
            $table->string('property_type');            // Tipo: Casa, Apartamento, Finca, Lote, etc.
            $table->string('status')->default('active'); 
            
            $table->string('department');               // Ej: Caldas
            $table->string('city');                     // Ej: Manizales
            $table->string('neighborhood')->nullable(); // Ej: Chipre

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
