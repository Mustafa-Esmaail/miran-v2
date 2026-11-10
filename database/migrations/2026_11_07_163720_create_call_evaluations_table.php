<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crm_activity_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('call_phone', 30)->nullable()->index();
            $table->date('call_date');
            $table->json('scores');
            $table->json('notes')->nullable();
            $table->text('general_notes')->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->string('level', 30)->nullable();
            $table->timestamp('discussed_at')->nullable();
            $table->foreignId('discussed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'call_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_evaluations');
    }
};
