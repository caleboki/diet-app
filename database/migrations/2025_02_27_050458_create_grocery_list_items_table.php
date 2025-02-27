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
        Schema::create('grocery_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name'); // In case ingredient is deleted or custom item
            $table->string('quantity');
            $table->string('unit')->nullable();
            $table->string('category')->nullable(); // e.g., produce, dairy, etc.
            $table->boolean('is_purchased')->default(false);
            $table->foreignId('recipe_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grocery_list_items');
    }
};
