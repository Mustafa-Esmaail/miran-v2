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
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['status', 'category_id', 'delivery_type', 'course_level']);
        });

        Schema::table('course_groups', function (Blueprint $table) {
            $table->index(['status', 'start_at', 'enrollment_deadline_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->index(['is_active', 'category_id']);
        });

        Schema::table('coupon_targets', function (Blueprint $table) {
            $table->index(['coupon_id', 'target_type', 'target_id']);
        });

        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->index(['coupon_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status', 'category_id', 'delivery_type', 'course_level']);
        });

        Schema::table('course_groups', function (Blueprint $table) {
            $table->dropIndex(['status', 'start_at', 'enrollment_deadline_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'category_id']);
        });

        Schema::table('coupon_targets', function (Blueprint $table) {
            $table->dropIndex(['coupon_id', 'target_type', 'target_id']);
        });

        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropIndex(['coupon_id', 'user_id']);
        });
    }
};
