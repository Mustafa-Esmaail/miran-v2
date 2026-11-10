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
            $table->foreignId('zoom_host_user_id')->nullable()->after('course_curriculum_section_id')->constrained('users')->nullOnDelete();
            $table->string('zoom_host_email')->nullable()->after('zoom_host_user_id');
            $table->string('zoom_meeting_id')->nullable()->after('recording_url');
            $table->string('zoom_meeting_uuid')->nullable()->after('zoom_meeting_id');
            $table->longText('zoom_start_url')->nullable()->after('zoom_meeting_uuid');
            $table->timestamp('zoom_synced_at')->nullable()->after('zoom_start_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_group_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('zoom_host_user_id');
            $table->dropColumn([
                'zoom_host_email',
                'zoom_meeting_id',
                'zoom_meeting_uuid',
                'zoom_start_url',
                'zoom_synced_at',
            ]);
        });
    }
};
