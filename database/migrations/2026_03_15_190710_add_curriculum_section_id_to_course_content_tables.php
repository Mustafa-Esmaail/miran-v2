<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('course_curriculum_section_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_curriculum_sections')
                ->nullOnDelete();
        });

        Schema::table('course_attachments', function (Blueprint $table) {
            $table->foreignId('course_curriculum_section_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_curriculum_sections')
                ->nullOnDelete();
        });

        Schema::table('course_group_sessions', function (Blueprint $table) {
            $table->foreignId('course_curriculum_section_id')
                ->nullable()
                ->after('course_group_id')
                ->constrained('course_curriculum_sections')
                ->nullOnDelete();
        });

        $this->backfillCurriculumSections();
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_curriculum_section_id');
        });

        Schema::table('course_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_curriculum_section_id');
        });

        Schema::table('course_group_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_curriculum_section_id');
        });
    }

    private function backfillCurriculumSections(): void
    {
        $courseIds = collect(DB::table('lessons')->pluck('course_id'))
            ->merge(DB::table('course_attachments')->pluck('course_id'))
            ->merge(
                DB::table('course_group_sessions')
                    ->join('course_groups', 'course_groups.id', '=', 'course_group_sessions.course_group_id')
                    ->pluck('course_groups.course_id')
            )
            ->filter()
            ->unique()
            ->values();

        foreach ($courseIds as $courseId) {
            $sectionId = DB::table('course_curriculum_sections')->insertGetId([
                'course_id' => $courseId,
                'title' => json_encode([
                    'en' => 'Course Content',
                    'ar' => 'محتوى الدورة',
                ], JSON_UNESCAPED_UNICODE),
                'description' => null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lessons')
                ->where('course_id', $courseId)
                ->whereNull('course_curriculum_section_id')
                ->update(['course_curriculum_section_id' => $sectionId]);

            DB::table('course_attachments')
                ->where('course_id', $courseId)
                ->whereNull('course_curriculum_section_id')
                ->update(['course_curriculum_section_id' => $sectionId]);

            DB::table('course_group_sessions')
                ->join('course_groups', 'course_groups.id', '=', 'course_group_sessions.course_group_id')
                ->where('course_groups.course_id', $courseId)
                ->whereNull('course_group_sessions.course_curriculum_section_id')
                ->update(['course_group_sessions.course_curriculum_section_id' => $sectionId]);
        }
    }
};
