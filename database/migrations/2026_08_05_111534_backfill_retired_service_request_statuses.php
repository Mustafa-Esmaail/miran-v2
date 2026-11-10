<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map of retired ServiceRequestStatus enum values to their closest
     * replacement in the new disposition list.
     */
    private const MAPPING = [
        'triaged' => 'follow_up_dated',
        'in_progress' => 'follow_up_dated',
        'waiting_customer' => 'awaiting_payment',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::MAPPING as $old => $new) {
            DB::table('service_requests')->where('status', $old)->update(['status' => $new]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::MAPPING as $old => $new) {
            DB::table('service_requests')->where('status', $new)->update(['status' => $old]);
        }
    }
};
