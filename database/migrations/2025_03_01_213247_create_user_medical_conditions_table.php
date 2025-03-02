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
        Schema::create('user_medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_dietary_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('medical_condition_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['mild', 'moderate', 'severe'])->default('moderate');
            $table->timestamps();
            
            $table->unique(['user_dietary_profile_id', 'medical_condition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_medical_conditions');
    }
};
