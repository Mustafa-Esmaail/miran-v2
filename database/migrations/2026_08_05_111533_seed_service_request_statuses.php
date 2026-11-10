<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $rows = [
            ['name' => ['ar' => 'جديد', 'en' => 'New'], 'slug' => 'new', 'color' => '#f59e0b', 'outcome' => 'open', 'is_default' => true, 'is_spam' => false, 'sort_order' => 1],
            ['name' => ['ar' => 'واتس تعريفي'], 'slug' => 'whatsapp_intro', 'color' => '#3b82f6', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 2],
            ['name' => ['ar' => 'مشغول حاليا'], 'slug' => 'currently_busy', 'color' => '#f59e0b', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 3],
            ['name' => ['ar' => 'لم يتم الرد 2'], 'slug' => 'no_answer_2', 'color' => '#f59e0b', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 4],
            ['name' => ['ar' => 'لم يتم الرد 3'], 'slug' => 'no_answer_3', 'color' => '#f59e0b', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 5],
            ['name' => ['ar' => 'متابعة بتاريخ'], 'slug' => 'follow_up_dated', 'color' => '#6366f1', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 6],
            ['name' => ['ar' => 'انتظار دفع'], 'slug' => 'awaiting_payment', 'color' => '#6366f1', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 7],
            ['name' => ['ar' => 'لو ناسبني بتواصل'], 'slug' => 'contact_if_suitable', 'color' => '#6b7280', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 8],
            ['name' => ['ar' => 'دف قادمه'], 'slug' => 'payment_upcoming', 'color' => '#6366f1', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 9],
            ['name' => ['ar' => 'نهائي متابعه بتاريخ'], 'slug' => 'final_follow_up_dated', 'color' => '#ef4444', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 10],
            ['name' => ['ar' => 'نهائي انتظار دفع'], 'slug' => 'final_awaiting_payment', 'color' => '#ef4444', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 11],
            ['name' => ['ar' => 'نهائي مشغول حاليا'], 'slug' => 'final_currently_busy', 'color' => '#ef4444', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 12],
            ['name' => ['ar' => 'نهائي دفعه قادمه'], 'slug' => 'final_payment_upcoming', 'color' => '#ef4444', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 13],
            ['name' => ['ar' => 'مايمسك الخط'], 'slug' => 'not_answering', 'color' => '#6b7280', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 14],
            ['name' => ['ar' => 'تم الدفع'], 'slug' => 'payment_completed', 'color' => '#22c55e', 'outcome' => 'won', 'is_default' => false, 'is_spam' => false, 'sort_order' => 15],
            ['name' => ['ar' => 'رقم حاطئ'], 'slug' => 'wrong_number', 'color' => '#6b7280', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 16],
            ['name' => ['ar' => 'رقم مكرر'], 'slug' => 'duplicate_number', 'color' => '#6b7280', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 17],
            ['name' => ['ar' => 'سعر غير مناسب'], 'slug' => 'price_not_suitable', 'color' => '#6b7280', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 18],
            ['name' => ['ar' => 'دوره غير متوفرة'], 'slug' => 'course_unavailable', 'color' => '#6b7280', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 19],
            ['name' => ['ar' => 'مغلق الخط'], 'slug' => 'line_closed', 'color' => '#6b7280', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 20],
            ['name' => ['ar' => 'استفسار'], 'slug' => 'inquiry', 'color' => '#3b82f6', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 21],
            ['name' => ['ar' => 'تست test'], 'slug' => 'test_status', 'color' => '#6b7280', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 22],
            ['name' => ['ar' => 'معيّن', 'en' => 'Assigned'], 'slug' => 'assigned', 'color' => '#3b82f6', 'outcome' => 'open', 'is_default' => false, 'is_spam' => false, 'sort_order' => 23],
            ['name' => ['ar' => 'تم الحل', 'en' => 'Resolved'], 'slug' => 'resolved', 'color' => '#22c55e', 'outcome' => 'won', 'is_default' => false, 'is_spam' => false, 'sort_order' => 24],
            ['name' => ['ar' => 'مغلق', 'en' => 'Closed'], 'slug' => 'closed', 'color' => '#6b7280', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => false, 'sort_order' => 25],
            ['name' => ['ar' => 'بريد مزعج', 'en' => 'Spam'], 'slug' => 'spam', 'color' => '#ef4444', 'outcome' => 'lost', 'is_default' => false, 'is_spam' => true, 'sort_order' => 26],
        ];

        foreach ($rows as $row) {
            DB::table('service_request_statuses')->insert([
                'name' => json_encode($row['name']),
                'slug' => $row['slug'],
                'color' => $row['color'],
                'outcome' => $row['outcome'],
                'sources' => null,
                'is_default' => $row['is_default'],
                'is_spam' => $row['is_spam'],
                'is_active' => true,
                'sort_order' => $row['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('service_request_statuses')->whereIn('slug', [
            'new', 'whatsapp_intro', 'currently_busy', 'no_answer_2', 'no_answer_3',
            'follow_up_dated', 'awaiting_payment', 'contact_if_suitable', 'payment_upcoming',
            'final_follow_up_dated', 'final_awaiting_payment', 'final_currently_busy', 'final_payment_upcoming',
            'not_answering', 'payment_completed', 'wrong_number', 'duplicate_number', 'price_not_suitable',
            'course_unavailable', 'line_closed', 'inquiry', 'test_status',
            'assigned', 'resolved', 'closed', 'spam',
        ])->delete();
    }
};
