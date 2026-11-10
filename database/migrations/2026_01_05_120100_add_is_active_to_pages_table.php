<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'is_active')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('template');
            });
        }

        if (Schema::hasColumn('pages', 'is_published')) {
            DB::table('pages')->whereNull('is_active')->update(['is_active' => true]);
            DB::table('pages')->update(['is_active' => DB::raw('is_published')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'is_active')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
