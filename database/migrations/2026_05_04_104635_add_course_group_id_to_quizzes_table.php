<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('course_group_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\CourseGroup::class);
            $table->dropColumn('course_group_id');
        });
    }
};
