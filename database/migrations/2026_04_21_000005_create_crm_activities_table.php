<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table): void {
            $table->id();
            $table->morphs('entity');
            $table->string('type');
            $table->string('subject', 190);
            $table->text('body')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
