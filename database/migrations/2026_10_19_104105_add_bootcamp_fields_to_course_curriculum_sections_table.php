<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_curriculum_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('week')->nullable()->after('sort_order');
            $table->json('badge')->nullable()->after('week');
            $table->json('outcome')->nullable()->after('badge');
            $table->json('bullets')->nullable()->after('outcome');
            $table->boolean('is_featured')->default(false)->after('bullets');
            $table->boolean('is_capstone')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('course_curriculum_sections', function (Blueprint $table) {
            $table->dropColumn(['week', 'badge', 'outcome', 'bullets', 'is_featured', 'is_capstone']);
        });
    }
};
