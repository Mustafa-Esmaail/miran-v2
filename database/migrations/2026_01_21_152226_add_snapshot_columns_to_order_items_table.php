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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('title_snapshot')->nullable()->after('purchasable_id');
            $table->string('currency', 10)->nullable()->after('title_snapshot');
            $table->json('metadata_snapshot')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['title_snapshot', 'currency', 'metadata_snapshot']);
        });
    }
};
