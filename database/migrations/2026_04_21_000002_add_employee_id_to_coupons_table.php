<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->foreignId('employee_id')->nullable()->after('is_active')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Models\Employee::class);
            $table->dropColumn('employee_id');
        });
    }
};
