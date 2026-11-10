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
        Schema::create('nelc_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->text('response')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nelc_sync_logs');
    }
};
