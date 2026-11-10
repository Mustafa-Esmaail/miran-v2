<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_event');          // WorkflowTrigger enum value
            $table->json('conditions')->nullable();   // [{field, operator, value}]
            $table->json('actions');                  // [{type, ...params}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trigger_event', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
