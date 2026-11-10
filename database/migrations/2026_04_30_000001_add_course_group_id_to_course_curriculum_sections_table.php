<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_curriculum_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('course_curriculum_sections', 'course_group_id')) {
                $table->foreignId('course_group_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasIndex('course_curriculum_sections', 'ccs_course_group_sort_idx')) {
                $table->index(['course_id', 'course_group_id', 'sort_order'], 'ccs_course_group_sort_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_curriculum_sections', function (Blueprint $table) {
            if (Schema::hasIndex('course_curriculum_sections', 'ccs_course_group_sort_idx')) {
                $table->dropIndex('ccs_course_group_sort_idx');
            }

            if (Schema::hasColumn('course_curriculum_sections', 'course_group_id')) {
                $table->dropForeign(['course_group_id']);
                $table->dropColumn('course_group_id');
            }
        });
    }
};
