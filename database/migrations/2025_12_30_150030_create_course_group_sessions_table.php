<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_group_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->dateTime('start_at');
            $table->integer('duration_minutes');
            $table->string('join_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_group_sessions');
    }
};
