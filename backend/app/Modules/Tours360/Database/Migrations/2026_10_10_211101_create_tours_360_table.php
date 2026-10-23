<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('caption')->nullable();
            $table->string('image_url');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tour_id', 'sort_order']);
        });

        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_scene_id')->constrained('scenes')->cascadeOnDelete();
            $table->string('label');
            // Posición esférica del punto (radianes)
            $table->float('yaw');    // rotación alrededor del eje Y (horizontal)
            $table->float('pitch');  // inclinación arriba/abajo (vertical)
            $table->timestamps();
            $table->index('scene_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspots');
        Schema::dropIfExists('scenes');
        Schema::dropIfExists('tours');
    }
};
