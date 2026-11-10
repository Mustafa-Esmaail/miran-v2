<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bundleType = \App\Models\Bundle::class;
        $courseGroupType = \App\Models\CourseGroup::class;
        $courseType = \App\Models\Course::class;

        DB::table('cart_items')
            ->whereNotNull('bundle_id')
            ->update([
                'purchasable_type' => $bundleType,
                'purchasable_id' => DB::raw('bundle_id'),
            ]);

        DB::table('cart_items')
            ->whereNull('bundle_id')
            ->whereNotNull('course_group_id')
            ->update([
                'purchasable_type' => $courseGroupType,
                'purchasable_id' => DB::raw('course_group_id'),
            ]);

        DB::table('order_items')
            ->whereNotNull('bundle_id')
            ->update([
                'purchasable_type' => $bundleType,
                'purchasable_id' => DB::raw('bundle_id'),
            ]);

        DB::table('order_items')
            ->whereNull('bundle_id')
            ->whereNotNull('course_group_id')
            ->update([
                'purchasable_type' => $courseGroupType,
                'purchasable_id' => DB::raw('course_group_id'),
            ]);

        DB::table('wishlist_items')
            ->whereNotNull('bundle_id')
            ->update([
                'wishable_type' => $bundleType,
                'wishable_id' => DB::raw('bundle_id'),
            ]);

        DB::table('wishlist_items')
            ->whereNull('bundle_id')
            ->whereNotNull('course_id')
            ->update([
                'wishable_type' => $courseType,
                'wishable_id' => DB::raw('course_id'),
            ]);

        $fallbackCurrency = config('lms.currency', 'SAR');

        DB::statement("UPDATE order_items oi
            JOIN bundles b ON b.id = oi.purchasable_id
            LEFT JOIN (
                SELECT bundle_id, COUNT(*) as course_count
                FROM bundle_courses
                GROUP BY bundle_id
            ) bc ON bc.bundle_id = b.id
            JOIN orders o ON o.id = oi.order_id
            SET oi.title_snapshot = COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(b.title, '$.en')),
                JSON_UNQUOTE(JSON_EXTRACT(b.title, '$.ar')),
                ''
            ),
            oi.currency = COALESCE(o.currency, '{$fallbackCurrency}'),
            oi.metadata_snapshot = JSON_OBJECT(
                'bundle_id', b.id,
                'type_label', 'bundle',
                'bundle_course_count', COALESCE(bc.course_count, 0),
                'subtitle', CONCAT('Bundle · ', COALESCE(bc.course_count, 0), ' courses'),
                'image_path', b.image_path
            )
            WHERE oi.purchasable_type = '{$bundleType}'");

        DB::statement("UPDATE order_items oi
            JOIN course_groups cg ON cg.id = oi.purchasable_id
            JOIN courses c ON c.id = cg.course_id
            JOIN orders o ON o.id = oi.order_id
            SET oi.title_snapshot = COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(c.title, '$.en')),
                JSON_UNQUOTE(JSON_EXTRACT(c.title, '$.ar')),
                ''
            ),
            oi.currency = COALESCE(o.currency, '{$fallbackCurrency}'),
            oi.metadata_snapshot = JSON_OBJECT(
                'course_group_id', cg.id,
                'course_id', c.id,
                'type_label', 'course_group',
                'course_slug', c.slug,
                'group_title', COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(cg.title, '$.en')),
                    JSON_UNQUOTE(JSON_EXTRACT(cg.title, '$.ar')),
                    ''
                ),
                'subtitle', CONCAT('Course Group · ', COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(cg.title, '$.en')),
                    JSON_UNQUOTE(JSON_EXTRACT(cg.title, '$.ar')),
                    ''
                )),
                'image_path', c.image_path
            )
            WHERE oi.purchasable_type = '{$courseGroupType}'");

        DB::statement('ALTER TABLE cart_items MODIFY purchasable_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cart_items MODIFY purchasable_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_items MODIFY purchasable_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE order_items MODIFY purchasable_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE wishlist_items MODIFY wishable_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE wishlist_items MODIFY wishable_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_items MODIFY title_snapshot VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE order_items MODIFY currency VARCHAR(10) NOT NULL');

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'purchasable_type', 'purchasable_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unique(['order_id', 'purchasable_type', 'purchasable_id']);
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->unique(['user_id', 'wishable_type', 'wishable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'purchasable_type', 'purchasable_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'purchasable_type', 'purchasable_id']);
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'wishable_type', 'wishable_id']);
        });

        DB::statement('ALTER TABLE cart_items MODIFY purchasable_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cart_items MODIFY purchasable_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE order_items MODIFY purchasable_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE order_items MODIFY purchasable_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE wishlist_items MODIFY wishable_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE wishlist_items MODIFY wishable_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE order_items MODIFY title_snapshot VARCHAR(255) NULL');
        DB::statement('ALTER TABLE order_items MODIFY currency VARCHAR(10) NULL');
    }
};
