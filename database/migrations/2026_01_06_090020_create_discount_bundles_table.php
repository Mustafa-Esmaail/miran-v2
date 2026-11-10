<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['discount_id', 'bundle_id'], 'discount_bundles_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_bundles');
    }
};
