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
        Schema::create('question_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->nullable()->constrained('course_questions')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('question_answers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_helpful')->default(true);
            $table->timestamps();

            $table->unique(['question_id', 'user_id']);
            $table->unique(['answer_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_votes');
    }
};
