<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing statuses, grouped by the Talabat stage slug they belong to.
     */
    private const STAGE_STATUS_SLUGS = [
        'contact' => ['no_answer_2', 'no_answer_3', 'currently_busy', 'closed', 'not_answering', 'final_currently_busy'],
        'follow_up' => ['follow_up_dated', 'payment_upcoming', 'final_follow_up_dated', 'awaiting_payment', 'final_awaiting_payment'],
        'not_interested' => ['wrong_number', 'inquiry', 'contact_if_suitable', 'course_unavailable', 'price_not_suitable'],
        'pay' => ['payment_completed'],
    ];

    /**
     * New statuses that don't exist yet, keyed by the stage slug they belong to.
     */
    private const NEW_STATUSES = [
        'contact' => [
            ['slug' => 'no_answer_1', 'name' => ['ar' => 'لم يتم الرد 1'], 'color' => '#f59e0b', 'outcome' => 'open', 'sort_order' => 27],
            ['slug' => 'final_no_contact', 'name' => ['ar' => 'نهائي لم نتواصل'], 'color' => '#ef4444', 'outcome' => 'lost', 'sort_order' => 28],
        ],
        'not_interested' => [
            ['slug' => 'not_serious', 'name' => ['ar' => 'غير جاد'], 'color' => '#6b7280', 'outcome' => 'lost', 'sort_order' => 29],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $talabatTypeId = DB::table('service_request_types')->where('slug', 'tlbat')->value('id');

        if (! $talabatTypeId) {
            return;
        }

        $stageIds = DB::table('service_request_stages')
            ->where('type_id', $talabatTypeId)
            ->pluck('id', 'slug');

        foreach (self::STAGE_STATUS_SLUGS as $stageSlug => $statusSlugs) {
            if (! isset($stageIds[$stageSlug])) {
                continue;
            }

            DB::table('service_request_statuses')
                ->whereIn('slug', $statusSlugs)
                ->update(['stage_id' => $stageIds[$stageSlug]]);
        }

        $now = now();

        foreach (self::NEW_STATUSES as $stageSlug => $statuses) {
            if (! isset($stageIds[$stageSlug])) {
                continue;
            }

            foreach ($statuses as $status) {
                DB::table('service_request_statuses')->insert([
                    'name' => json_encode($status['name']),
                    'slug' => $status['slug'],
                    'color' => $status['color'],
                    'outcome' => $status['outcome'],
                    'stage_id' => $stageIds[$stageSlug],
                    'is_default' => false,
                    'is_spam' => false,
                    'requires_date' => false,
                    'is_active' => true,
                    'sort_order' => $status['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $allExistingSlugs = collect(self::STAGE_STATUS_SLUGS)->flatten()->all();
        DB::table('service_request_statuses')->whereIn('slug', $allExistingSlugs)->update(['stage_id' => null]);

        $newSlugs = collect(self::NEW_STATUSES)->flatten(1)->pluck('slug')->all();
        DB::table('service_request_statuses')->whereIn('slug', $newSlugs)->delete();
    }
};
