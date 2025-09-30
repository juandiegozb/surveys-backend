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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('respondent_id', 100)->index();
            $table->string('respondent_type', 20)->default('anonymous');

            $table->text('answer_text')->nullable();
            $table->json('answer_data')->nullable();
            $table->string('file_url')->nullable();
            $table->json('attachments')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['survey_id', 'submitted_at']);
            $table->index(['question_id', 'submitted_at']);
            $table->index(['respondent_id', 'respondent_type']);
            $table->index(['survey_id', 'respondent_id']);
            $table->index(['submitted_at']);

            $table->index(['survey_id', 'respondent_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
