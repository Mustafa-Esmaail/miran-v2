<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wrap any existing plain string paths into a JSON array
        DB::table('course_attachments')->get()->each(function (object $row): void {
            $raw = $row->file_path;
            if ($raw !== null && $raw !== '' && ! str_starts_with($raw, '[')) {
                DB::table('course_attachments')
                    ->where('id', $row->id)
                    ->update(['file_path' => json_encode([$raw])]);
            }
        });

        Schema::table('course_attachments', function (Blueprint $table): void {
            $table->json('file_path')->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_attachments', function (Blueprint $table): void {
            $table->string('file_path')->change();
        });
    }
};
