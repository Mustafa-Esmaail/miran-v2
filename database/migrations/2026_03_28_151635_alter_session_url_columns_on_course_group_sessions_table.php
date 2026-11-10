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
            $table->longText('join_url')->nullable()->change();
            $table->longText('recording_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_group_sessions', function (Blueprint $table): void {
            $table->string('join_url')->nullable()->change();
            $table->string('recording_url')->nullable()->change();
        });
    }
};
