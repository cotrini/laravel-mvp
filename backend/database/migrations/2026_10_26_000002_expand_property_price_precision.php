<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('price', 13, 2)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('properties')->where('price', '>', 9999999999.99)->exists()) {
            throw new RuntimeException('Cannot reduce property price precision while listings exceed COP 9,999,999,999.99.');
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->change();
        });
    }
};
