<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('order_no', 'like', 'ADMIN-%')
            ->update(['source' => 'admin']);

        DB::table('orders')
            ->where('order_no', 'like', 'ORD-%')
            ->whereNotNull('referred_by_employee_id')
            ->update(['source' => 'sales']);

        DB::table('orders')
            ->where('order_no', 'like', 'ORD-%')
            ->whereNull('referred_by_employee_id')
            ->update(['source' => 'store']);
    }

    public function down(): void
    {
        DB::table('orders')->update(['source' => 'store']);
    }
};
