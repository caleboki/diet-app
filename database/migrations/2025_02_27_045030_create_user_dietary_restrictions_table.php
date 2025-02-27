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
        Schema::create('user_dietary_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_dietary_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('dietary_restriction_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['must_avoid', 'limit', 'monitor'])->default('must_avoid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dietary_restrictions');
    }
};
