<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'content') || Schema::hasColumn('pages', 'body')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true)) {
            DB::statement('ALTER TABLE pages RENAME COLUMN content TO body');

            return;
        }

        Schema::table('pages', function ($table) {
            $table->renameColumn('content', 'body');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pages', 'body') || Schema::hasColumn('pages', 'content')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true)) {
            DB::statement('ALTER TABLE pages RENAME COLUMN body TO content');

            return;
        }

        Schema::table('pages', function ($table) {
            $table->renameColumn('body', 'content');
        });
    }
};
