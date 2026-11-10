<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_curriculum_section_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->longText('description')->nullable();
            $table->json('attachment_path')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->unsignedTinyInteger('max_grade')->default(10);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'course_group_id', 'course_curriculum_section_id'], 'assignments_course_group_section_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
