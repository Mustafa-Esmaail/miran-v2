<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Statuses where a specific date (follow-up or payment due date) is
     * part of the disposition itself, so the request form should collect one.
     */
    private const DATE_RELATED_SLUGS = [
        'follow_up_dated',
        'final_follow_up_dated',
        'awaiting_payment',
        'final_awaiting_payment',
        'payment_upcoming',
        'final_payment_upcoming',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('service_request_statuses')
            ->whereIn('slug', self::DATE_RELATED_SLUGS)
            ->update(['requires_date' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('service_request_statuses')
            ->whereIn('slug', self::DATE_RELATED_SLUGS)
            ->update(['requires_date' => false]);
    }
};
