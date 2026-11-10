<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });

        DB::statement('ALTER TABLE enrollments MODIFY order_item_id BIGINT UNSIGNED NULL');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });

        DB::statement('ALTER TABLE enrollments MODIFY order_item_id BIGINT UNSIGNED NOT NULL');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });
    }
};
