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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('landable_type')->nullable()->after('template');
            $table->unsignedBigInteger('landable_id')->nullable()->after('landable_type');
            $table->index(['landable_type', 'landable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['landable_type', 'landable_id']);
            $table->dropColumn(['landable_type', 'landable_id']);
        });
    }
};
