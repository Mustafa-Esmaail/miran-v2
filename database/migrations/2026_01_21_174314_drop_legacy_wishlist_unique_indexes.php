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
        if (! Schema::hasTable('wishlist_items')) {
            return;
        }

        if ($this->indexExists('wishlist_items_user_id_course_id_unique')) {
            DB::statement('DROP INDEX wishlist_items_user_id_course_id_unique ON wishlist_items');
        }

        if ($this->indexExists('wishlist_items_user_id_bundle_id_unique')) {
            DB::statement('DROP INDEX wishlist_items_user_id_bundle_id_unique ON wishlist_items');
        }

        if (! $this->indexExists('wishlist_items_user_id_wishable_type_wishable_id_unique')) {
            Schema::table('wishlist_items', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'wishable_type', 'wishable_id'],
                    'wishlist_items_user_id_wishable_type_wishable_id_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('wishlist_items')) {
            return;
        }

        Schema::table('wishlist_items', function (Blueprint $table) {
            if (Schema::hasColumn('wishlist_items', 'course_id') &&
                ! $this->indexExists('wishlist_items_user_id_course_id_unique')) {
                $table->unique(['user_id', 'course_id'], 'wishlist_items_user_id_course_id_unique');
            }

            if (Schema::hasColumn('wishlist_items', 'bundle_id') &&
                ! $this->indexExists('wishlist_items_user_id_bundle_id_unique')) {
                $table->unique(['user_id', 'bundle_id'], 'wishlist_items_user_id_bundle_id_unique');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            ['wishlist_items', $indexName]
        );

        return ! empty($result);
    }
};
