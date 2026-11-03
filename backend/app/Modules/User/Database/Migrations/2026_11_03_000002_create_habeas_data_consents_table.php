<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('habeas_data_accepted_at')->nullable();
        });

        Schema::create('habeas_data_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('policy_version', 20);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['user_id', 'policy_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habeas_data_consents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('habeas_data_accepted_at');
        });
    }
};
