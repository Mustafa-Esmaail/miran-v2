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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('allow_resubmit')->default(false)->after('passing_score');
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('allow_resubmit');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['allow_resubmit', 'max_attempts']);
        });
    }
};
