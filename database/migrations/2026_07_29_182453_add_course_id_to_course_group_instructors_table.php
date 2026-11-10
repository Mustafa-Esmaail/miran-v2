<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_group_instructors', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement(
            'UPDATE course_group_instructors cgi '.
            'JOIN course_groups cg ON cg.id = cgi.course_group_id '.
            'SET cgi.course_id = cg.course_id'
        );

        DB::statement('ALTER TABLE course_group_instructors MODIFY course_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE course_group_instructors MODIFY course_group_id BIGINT UNSIGNED NULL');

        Schema::table('course_group_instructors', function (Blueprint $table) {
            $table->index('course_group_id');
            $table->dropUnique(['course_group_id', 'user_id']);
            $table->unique(['course_id', 'course_group_id', 'user_id'], 'cgi_course_group_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('course_group_instructors', function (Blueprint $table) {
            $table->index('course_id', 'cgi_course_id_index');
            $table->dropUnique('cgi_course_group_user_unique');
        });

        DB::statement('ALTER TABLE course_group_instructors MODIFY course_group_id BIGINT UNSIGNED NOT NULL');

        Schema::table('course_group_instructors', function (Blueprint $table) {
            $table->unique(['course_group_id', 'user_id']);
            $table->dropIndex(['course_group_id']);
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};
