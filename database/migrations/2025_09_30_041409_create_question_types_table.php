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
        Schema::create('question_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // rating, comment-only, multiple-choice, etc.
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('allows_images')->default(false);
            $table->boolean('allows_multiple_answers')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_types');
    }
};
