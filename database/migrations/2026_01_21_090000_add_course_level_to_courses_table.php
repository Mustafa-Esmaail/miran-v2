<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'course_level')) {
                $table->string('course_level')->default('beginner')->after('delivery_type')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'course_level')) {
                $table->dropIndex(['course_level']);
                $table->dropColumn('course_level');
            }
        });
    }
};
