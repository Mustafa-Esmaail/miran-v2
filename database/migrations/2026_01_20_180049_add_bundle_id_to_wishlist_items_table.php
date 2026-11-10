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
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'bundle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'bundle_id']);
            $table->dropConstrainedForeignId('bundle_id');
        });
    }
};
