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
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('half_bathrooms')->nullable();
            $table->unsignedTinyInteger('parking_spaces')->nullable();
            $table->decimal('built_area_m2', 10, 2)->nullable();
            $table->decimal('lot_area_m2', 10, 2)->nullable();
            $table->unsignedSmallInteger('floor_number')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->unsignedTinyInteger('stratum')->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->decimal('administration_fee', 12, 2)->nullable();
            $table->boolean('is_furnished')->nullable();
            $table->boolean('has_elevator')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'bedrooms',
                'bathrooms',
                'half_bathrooms',
                'parking_spaces',
                'built_area_m2',
                'lot_area_m2',
                'floor_number',
                'total_floors',
                'stratum',
                'year_built',
                'administration_fee',
                'is_furnished',
                'has_elevator',
            ]);
        });
    }
};
