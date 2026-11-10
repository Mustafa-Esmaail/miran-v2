<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::getDatabaseName();

        if ($database) {
            DB::statement("ALTER DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $tables = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');

        foreach ($tables as $table) {
            $name = $table->name ?? null;

            if (! $name) {
                continue;
            }

            DB::statement("ALTER TABLE `{$name}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        // Irreversible without data risk, left intentionally blank.
    }
};
