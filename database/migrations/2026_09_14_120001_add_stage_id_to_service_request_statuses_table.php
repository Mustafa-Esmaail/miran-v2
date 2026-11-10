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
        Schema::table('service_request_statuses', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->after('outcome')
                ->constrained('service_request_stages')->nullOnDelete();

            $table->dropColumn('sources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_request_statuses', function (Blueprint $table) {
            $table->json('sources')->nullable();

            $table->dropConstrainedForeignId('stage_id');
        });
    }
};
