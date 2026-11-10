<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * New statuses for the Complaint (Shakawi) tracker, keyed by the stage
     * slug they belong to. "Green flag" dispositions (not a genuine
     * complaint) are colored green with a 'won' outcome; the "red flag"
     * disposition (never contacted) is red with a 'lost' outcome.
     */
    private const STATUSES = [
        'general' => [
            ['slug' => 'complaint_invalid', 'name' => ['ar' => 'شكوى غير صحيحة'], 'color' => '#22c55e', 'outcome' => 'won', 'sort_order' => 30],
            ['slug' => 'complaint_inquiry', 'name' => ['ar' => 'استفسار'], 'color' => '#22c55e', 'outcome' => 'won', 'sort_order' => 31],
            ['slug' => 'complaint_duplicate', 'name' => ['ar' => 'مكرر'], 'color' => '#22c55e', 'outcome' => 'won', 'sort_order' => 32],
            ['slug' => 'complaint_no_customer_response', 'name' => ['ar' => 'عدم تجاوب مع العميل'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 33],
            ['slug' => 'complaint_not_contacted', 'name' => ['ar' => 'لم يتم التواصل'], 'color' => '#ef4444', 'outcome' => 'lost', 'sort_order' => 34],
            ['slug' => 'complaint_ad_or_job', 'name' => ['ar' => 'إعلان أو وظيفة'], 'color' => '#22c55e', 'outcome' => 'won', 'sort_order' => 35],
        ],
        'lms' => [
            ['slug' => 'complaint_course_details_mismatch', 'name' => ['ar' => 'اختلاف في تفاصيل الدورة عن الموقع'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 36],
            ['slug' => 'complaint_certificate_printing', 'name' => ['ar' => 'طباعة شهادة'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 37],
            ['slug' => 'complaint_poor_course_service', 'name' => ['ar' => 'سوء في خدمات الدورة التدريبية'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 38],
            ['slug' => 'complaint_course_format_change', 'name' => ['ar' => 'تغيرات في تنسيق الدورة'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 39],
            ['slug' => 'complaint_trainer_performance', 'name' => ['ar' => 'أداء المدرب في الدورة'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 40],
        ],
        'payments' => [
            ['slug' => 'complaint_refund_personal_reason', 'name' => ['ar' => 'طلب استرداد لسبب شخصي'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 41],
            ['slug' => 'complaint_charged_not_registered', 'name' => ['ar' => 'تم سحب المبلغ ولم يتم التسجيل'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 42],
            ['slug' => 'complaint_refund_delay', 'name' => ['ar' => 'تأخير الاسترداد'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 43],
            ['slug' => 'complaint_invoice', 'name' => ['ar' => 'فاتورة'], 'color' => '#6b7280', 'outcome' => 'open', 'sort_order' => 44],
        ],
        'late_complaints' => [
            ['slug' => 'complaint_late', 'name' => ['ar' => 'شكاوي متأخرة'], 'color' => '#ef4444', 'outcome' => 'open', 'sort_order' => 45],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $shakawiTypeId = DB::table('service_request_types')->where('slug', 'shakawi')->value('id');

        if (! $shakawiTypeId) {
            return;
        }

        $stageIds = DB::table('service_request_stages')
            ->where('type_id', $shakawiTypeId)
            ->pluck('id', 'slug');

        $now = now();

        foreach (self::STATUSES as $stageSlug => $statuses) {
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
        $slugs = collect(self::STATUSES)->flatten(1)->pluck('slug')->all();

        DB::table('service_request_statuses')->whereIn('slug', $slugs)->delete();
    }
};
