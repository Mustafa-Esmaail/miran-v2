<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_groups', 'group_price_hallala')) {
            DB::statement('ALTER TABLE `course_groups` CHANGE `group_price_hallala` `group_price_amount` INT NULL');
        }

        if (Schema::hasColumn('cart_items', 'price_hallala')) {
            DB::statement('ALTER TABLE `cart_items` CHANGE `price_hallala` `price_amount` INT NOT NULL');
        }

        if (Schema::hasColumn('order_items', 'price_hallala')) {
            DB::statement('ALTER TABLE `order_items` CHANGE `price_hallala` `price_amount` INT NOT NULL');
        }

        if (Schema::hasColumn('orders', 'subtotal_hallala')) {
            DB::statement('ALTER TABLE `orders` CHANGE `subtotal_hallala` `subtotal_amount` INT NOT NULL');
        }

        if (Schema::hasColumn('orders', 'discount_hallala')) {
            DB::statement('ALTER TABLE `orders` CHANGE `discount_hallala` `discount_amount` INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('orders', 'total_hallala')) {
            DB::statement('ALTER TABLE `orders` CHANGE `total_hallala` `total_amount` INT NOT NULL');
        }

        if (Schema::hasColumn('payments', 'amount_hallala')) {
            DB::statement('ALTER TABLE `payments` CHANGE `amount_hallala` `amount` INT NOT NULL');
        }

        if (Schema::hasColumn('coupons', 'min_order_hallala')) {
            DB::statement('ALTER TABLE `coupons` CHANGE `min_order_hallala` `min_order_amount` INT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_groups', 'group_price_amount')) {
            DB::statement('ALTER TABLE `course_groups` CHANGE `group_price_amount` `group_price_hallala` INT NULL');
        }

        if (Schema::hasColumn('cart_items', 'price_amount')) {
            DB::statement('ALTER TABLE `cart_items` CHANGE `price_amount` `price_hallala` INT NOT NULL');
        }

        if (Schema::hasColumn('order_items', 'price_amount')) {
            DB::statement('ALTER TABLE `order_items` CHANGE `price_amount` `price_hallala` INT NOT NULL');
        }

        if (Schema::hasColumn('orders', 'subtotal_amount')) {
            DB::statement('ALTER TABLE `orders` CHANGE `subtotal_amount` `subtotal_hallala` INT NOT NULL');
        }

        if (Schema::hasColumn('orders', 'discount_amount')) {
            DB::statement('ALTER TABLE `orders` CHANGE `discount_amount` `discount_hallala` INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('orders', 'total_amount')) {
            DB::statement('ALTER TABLE `orders` CHANGE `total_amount` `total_hallala` INT NOT NULL');
        }

        if (Schema::hasColumn('payments', 'amount')) {
            DB::statement('ALTER TABLE `payments` CHANGE `amount` `amount_hallala` INT NOT NULL');
        }

        if (Schema::hasColumn('coupons', 'min_order_amount')) {
            DB::statement('ALTER TABLE `coupons` CHANGE `min_order_amount` `min_order_hallala` INT NULL');
        }
    }
};
