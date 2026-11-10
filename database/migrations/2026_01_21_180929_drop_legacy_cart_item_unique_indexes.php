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
        if (! Schema::hasTable('cart_items')) {
            return;
        }

        if ($this->indexExists('cart_items_cart_id_course_group_id_unique')) {
            DB::statement('DROP INDEX cart_items_cart_id_course_group_id_unique ON cart_items');
        }

        if ($this->indexExists('cart_items_cart_id_course_id_unique')) {
            DB::statement('DROP INDEX cart_items_cart_id_course_id_unique ON cart_items');
        }

        if ($this->indexExists('cart_items_cart_id_bundle_id_unique')) {
            DB::statement('DROP INDEX cart_items_cart_id_bundle_id_unique ON cart_items');
        }

        if (! $this->indexExists('cart_items_cart_id_purchasable_type_purchasable_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(
                    ['cart_id', 'purchasable_type', 'purchasable_id'],
                    'cart_items_cart_id_purchasable_type_purchasable_id_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cart_items')) {
            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'course_group_id') &&
                ! $this->indexExists('cart_items_cart_id_course_group_id_unique')) {
                $table->unique(['cart_id', 'course_group_id'], 'cart_items_cart_id_course_group_id_unique');
            }

            if (Schema::hasColumn('cart_items', 'course_id') &&
                ! $this->indexExists('cart_items_cart_id_course_id_unique')) {
                $table->unique(['cart_id', 'course_id'], 'cart_items_cart_id_course_id_unique');
            }

            if (Schema::hasColumn('cart_items', 'bundle_id') &&
                ! $this->indexExists('cart_items_cart_id_bundle_id_unique')) {
                $table->unique(['cart_id', 'bundle_id'], 'cart_items_cart_id_bundle_id_unique');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            ['cart_items', $indexName]
        );

        return ! empty($result);
    }
};
