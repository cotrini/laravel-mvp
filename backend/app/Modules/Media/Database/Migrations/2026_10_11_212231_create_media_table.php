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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

            // Relación polimórfica (para que sirva en propiedades, usuarios, etc.)
            $table->morphs('mediable'); 
            
            // Tipo de contenido multimedia
            // Ejemplos: 'image', 'video', 'google_map_embed', 'virtual_tour_360', 'model_3d'
            $table->string('type')->default('image'); 
            
            // Ruta del archivo (si se sube local o a S3/R2) O URL externa (si es un iframe de Google Maps / Matterport 3D)
            $table->text('url_or_path'); 
            
            // Título o descripción opcional de la pieza (ej: "Vista de la cocina", "Recorrido sala principal")
            $table->string('title')->nullable(); 
            
            // Control visual
            $table->unsignedInteger('order')->default(0);  // Orden de aparición en la galería
            $table->boolean('is_primary')->default(false); // Si es la foto de portada principal
            
            // EL SECRETO PARA LA ESCALABILIDAD: Un campo JSON para configuraciones futuras
            // Aquí podrás guardar sin modificar la tabla: coordenadas de Google Maps, 
            // ángulos iniciales para el 360°, o configuraciones del visor 3D.
            $table->json('meta')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
