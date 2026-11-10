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
        $talabatTypeId = DB::table('service_request_types')->where('slug', 'tlbat')->value('id');

        if (! $talabatTypeId) {
            return;
        }

        $now = now();

        $stages = [
            ['slug' => 'contact', 'name' => ['ar' => 'مرحلة التواصل', 'en' => 'Contact'], 'color' => '#3b82f6', 'sort_order' => 1],
            ['slug' => 'follow_up', 'name' => ['ar' => 'مرحلة المتابعة', 'en' => 'Follow Up'], 'color' => '#6366f1', 'sort_order' => 2],
            ['slug' => 'not_interested', 'name' => ['ar' => 'مرحلة عدم الاهتمام', 'en' => 'Not Interested'], 'color' => '#ef4444', 'sort_order' => 3],
            ['slug' => 'pay', 'name' => ['ar' => 'مرحلة الدفع', 'en' => 'Pay'], 'color' => '#22c55e', 'sort_order' => 4],
        ];

        foreach ($stages as $stage) {
            DB::table('service_request_stages')->insert([
                'type_id' => $talabatTypeId,
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
        $talabatTypeId = DB::table('service_request_types')->where('slug', 'tlbat')->value('id');

        if (! $talabatTypeId) {
            return;
        }

        DB::table('service_request_stages')
            ->where('type_id', $talabatTypeId)
            ->whereIn('slug', ['contact', 'follow_up', 'not_interested', 'pay'])
            ->delete();
    }
};
