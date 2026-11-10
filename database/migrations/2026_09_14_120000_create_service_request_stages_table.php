<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_request_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('service_request_types')->cascadeOnDelete();
            $table->json('name');
            $table->string('slug');
            $table->string('color')->default('#6b7280');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type_id', 'slug']);
            $table->index(['type_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_stages');
    }
};
