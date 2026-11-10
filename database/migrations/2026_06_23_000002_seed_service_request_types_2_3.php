<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_request_types')->insert([
            [
                'name' => json_encode(['ar' => 'طلبات']),
                'slug' => 'tlbat',
                'sync_to_sheets' => true,
                'sheet_id' => '1j21P0o7KnmnKz8-y_r46rEkBRIbRUzxi6KdH0fYtvGQ',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['ar' => 'شكاوى']),
                'slug' => 'shakawi',
                'sync_to_sheets' => true,
                'sheet_id' => '1l-Eyf0u1zB_2JjO0Q_q2TkOIneI4VSkJynTf9x-c2E4',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('service_request_types')->whereIn('slug', ['tlbat', 'shakawi'])->delete();
    }
};
