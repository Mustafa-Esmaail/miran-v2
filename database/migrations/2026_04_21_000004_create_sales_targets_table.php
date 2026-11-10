<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period_type');
            $table->string('period', 10);
            $table->unsignedBigInteger('target_revenue')->default(0);
            $table->unsignedInteger('target_leads')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period']);
            $table->index(['period_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
