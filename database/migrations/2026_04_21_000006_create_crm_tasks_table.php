<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->morphs('entity');
            $table->string('title', 190);
            $table->timestamp('due_at')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('is_done')->default(false);
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'is_done']);
            $table->index(['due_at', 'is_done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
