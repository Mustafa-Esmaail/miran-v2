<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('course_trainers')
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('course_group_instructors')->updateOrInsert(
                        [
                            'course_id' => $row->course_id,
                            'course_group_id' => null,
                            'user_id' => $row->user_id,
                        ],
                        [
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        DB::table('course_trainers')
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('course_group_instructors')
                        ->where('course_id', $row->course_id)
                        ->where('user_id', $row->user_id)
                        ->whereNull('course_group_id')
                        ->delete();
                }
            });
    }
};
