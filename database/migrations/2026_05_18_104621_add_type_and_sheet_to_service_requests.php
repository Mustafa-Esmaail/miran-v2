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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('source')->constrained('service_request_types')->nullOnDelete();
            $table->timestamp('sheet_synced_at')->nullable()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn(['type_id', 'sheet_synced_at']);
        });
    }
};
