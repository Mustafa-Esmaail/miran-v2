<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_quality_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('name');
            $table->decimal('weight', 5, 2)->default(12.5);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_quality_criteria');
    }
};
