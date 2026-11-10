<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('stage')->default('prospecting');
            $table->unsignedBigInteger('value')->default(0); // stored in halala/cents
            $table->unsignedTinyInteger('probability')->default(0); // 0-100
            $table->date('expected_close_date')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'stage']);
            $table->index(['employee_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
