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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['course_group_id']);
            $table->dropForeign(['bundle_id']);
            $table->dropColumn(['course_id', 'course_group_id', 'bundle_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['course_group_id']);
            $table->dropForeign(['bundle_id']);
            $table->dropColumn(['course_id', 'course_group_id', 'bundle_id']);
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['bundle_id']);
            $table->dropColumn(['course_id', 'bundle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->after('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bundle_id')->nullable()->after('course_group_id')->constrained()->cascadeOnDelete();
            $table->unique(['cart_id', 'course_group_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->after('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bundle_id')->nullable()->after('course_group_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->foreignId('course_id')->after('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'course_id']);
            $table->unique(['user_id', 'bundle_id']);
        });
    }
};
