<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('conditions')->nullable(); // [{field, operator, value}]
            $table->json('employee_ids');            // [1, 2, 3]
            $table->string('strategy')->default('least_loaded');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_rules');
    }
};
