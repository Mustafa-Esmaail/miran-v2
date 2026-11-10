<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('id_number')->nullable();
            $table->string('first_name_ar')->nullable();
            $table->string('second_name_ar')->nullable();
            $table->string('third_name_ar')->nullable();
            $table->string('last_name_ar')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('qualification')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('first_name_en')->nullable();
            $table->string('second_name_en')->nullable();
            $table->string('third_name_en')->nullable();
            $table->string('last_name_en')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
