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
        $shakawiTypeId = DB::table('service_request_types')->where('slug', 'shakawi')->value('id');

        if (! $shakawiTypeId) {
            return;
        }

        $now = now();

        $stages = [
            ['slug' => 'general', 'name' => ['ar' => 'عام', 'en' => 'General'], 'color' => '#3b82f6', 'sort_order' => 1],
            ['slug' => 'lms', 'name' => ['ar' => 'الدورات', 'en' => 'LMS'], 'color' => '#6366f1', 'sort_order' => 2],
            ['slug' => 'payments', 'name' => ['ar' => 'المدفوعات', 'en' => 'Payments'], 'color' => '#f59e0b', 'sort_order' => 3],
            ['slug' => 'late_complaints', 'name' => ['ar' => 'شكاوي متأخرة', 'en' => 'Late Complaints'], 'color' => '#ef4444', 'sort_order' => 4],
        ];

        foreach ($stages as $stage) {
            DB::table('service_request_stages')->insert([
                'type_id' => $shakawiTypeId,
                'name' => json_encode($stage['name']),
                'slug' => $stage['slug'],
                'color' => $stage['color'],
                'is_active' => true,
                'sort_order' => $stage['sort_order'],
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
        $shakawiTypeId = DB::table('service_request_types')->where('slug', 'shakawi')->value('id');

        if (! $shakawiTypeId) {
            return;
        }

        DB::table('service_request_stages')
            ->where('type_id', $shakawiTypeId)
            ->whereIn('slug', ['general', 'lms', 'payments', 'late_complaints'])
            ->delete();
    }
};
