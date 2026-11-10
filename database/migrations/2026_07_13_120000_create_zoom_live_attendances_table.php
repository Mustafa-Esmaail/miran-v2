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
        Schema::create('zoom_live_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_group_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zoom_participant_id')->nullable();
            $table->string('participant_name')->nullable();
            $table->string('participant_email')->nullable();
            $table->string('role')->default('attendee');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->timestamps();

            $table->index(['course_group_session_id', 'participant_email'], 'zoom_live_attendances_session_email_idx');
            $table->index(['course_group_session_id', 'left_at'], 'zoom_live_attendances_session_left_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_live_attendances');
    }
};
