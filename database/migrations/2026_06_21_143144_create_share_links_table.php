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
        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type')->default('homepage'); // homepage | course | bundle
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->string('full_url');
            $table->timestamps();

            $table->index(['employee_id', 'target_type']);
        });
    }

    /**
     * Reverse the migrations.
     
     */
    public function down(): void
    {
        Schema::dropIfExists('share_links');
    }
};
