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
        Schema::table('bank_transfer_receipts', function (Blueprint $table) {
            $table->index('order_id');
        });

        Schema::table('bank_transfer_receipts', function (Blueprint $table) {
            $table->dropUnique('bank_transfer_receipts_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transfer_receipts', function (Blueprint $table) {
            $table->dropIndex('bank_transfer_receipts_order_id_index');
        });

        Schema::table('bank_transfer_receipts', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }
};
