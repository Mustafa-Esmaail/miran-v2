<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_group_sessions', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('title');
            $table->json('attachments')->nullable()->after('recording_url');
        });
    }

    public function down(): void
    {
        Schema::table('course_group_sessions', function (Blueprint $table) {
            $table->dropColumn(['content', 'attachments']);
        });
    }
};
