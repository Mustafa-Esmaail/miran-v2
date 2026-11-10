<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_no')->unique();
            $table->string('status')->default('pending');
            $table->string('currency')->default('SAR');
            $table->integer('subtotal_amount');
            $table->integer('discount_amount')->default(0);
            $table->integer('total_amount');
            $table->foreignId('coupon_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
