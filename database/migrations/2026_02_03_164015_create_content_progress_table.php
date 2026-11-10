<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->morphs('contentable');
            $table->timestamps();

            $table->unique(['user_id', 'contentable_type', 'contentable_id'], 'content_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_progress');
    }
};
