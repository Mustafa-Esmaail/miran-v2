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
        Schema::table('course_group_sessions', function (Blueprint $table): void {
            $table->dateTime('zoom_actual_ended_at')->nullable()->after('zoom_actual_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_group_sessions', function (Blueprint $table): void {
            $table->dropColumn('zoom_actual_ended_at');
        });
    }
};
