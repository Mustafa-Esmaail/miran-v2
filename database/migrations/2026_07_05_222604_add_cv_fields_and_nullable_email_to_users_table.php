<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cv_image_path')->nullable()->after('avatar_path');
            $table->string('cv_pdf_path')->nullable()->after('cv_image_path');
        });

        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cv_image_path', 'cv_pdf_path']);
        });
    }
};
