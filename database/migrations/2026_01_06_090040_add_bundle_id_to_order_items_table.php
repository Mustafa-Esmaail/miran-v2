<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('course_group_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['course_group_id']);
        });

        DB::statement('ALTER TABLE order_items MODIFY course_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE order_items MODIFY course_group_id BIGINT UNSIGNED NULL');

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('course_group_id')->references('id')->on('course_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropForeign(['course_id']);
            $table->dropForeign(['course_group_id']);
        });

        DB::statement('ALTER TABLE order_items MODIFY course_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_items MODIFY course_group_id BIGINT UNSIGNED NOT NULL');

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('course_group_id')->references('id')->on('course_groups')->cascadeOnDelete();
            $table->dropColumn('bundle_id');
        });
    }
};
