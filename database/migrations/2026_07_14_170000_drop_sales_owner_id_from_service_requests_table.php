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
        DB::table('service_requests')
            ->whereNull('assigned_to')
            ->whereNotNull('sales_owner_id')
            ->update(['assigned_to' => DB::raw('sales_owner_id')]);

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['sales_owner_id']);
            $table->dropIndex(['sales_owner_id', 'status']);
            $table->dropColumn('sales_owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('sales_owner_id')->nullable()->after('owner_team')->constrained('users')->nullOnDelete();
            $table->index(['sales_owner_id', 'status']);
        });
    }
};
