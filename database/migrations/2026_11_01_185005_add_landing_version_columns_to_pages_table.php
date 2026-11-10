<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every landing page is one version of a "landing line" (landing_key = the
     * slug ads point at). Existing landing pages become v1 of their own line.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('landing_key')->nullable()->after('slug')->index();
            $table->unsignedSmallInteger('version')->nullable()->after('landing_key');
            $table->string('version_name')->nullable()->after('version');
            $table->string('marketing_source')->nullable()->after('version_name');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->timestamp('drafted_at')->nullable()->after('published_at');
        });

        DB::table('pages')
            ->whereIn('template', ['landing-v2', 'landing-excel', 'landing-kpi', 'landing-powerbi'])
            ->update([
                'landing_key' => DB::raw('slug'),
                'version' => 1,
                'published_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['landing_key']);
            $table->dropColumn(['landing_key', 'version', 'version_name', 'marketing_source', 'published_at', 'drafted_at']);
        });
    }
};
