<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'duration_hours')) {
                $table->unsignedSmallInteger('duration_hours')->nullable()->after('sku');
            }

            if (! Schema::hasColumn('courses', 'duration_group')) {
                $table->string('duration_group')->nullable()->index()->after('duration_hours');
            }

            if (! Schema::hasColumn('courses', 'days_count')) {
                $table->unsignedSmallInteger('days_count')->nullable()->after('duration_group');
            }

            if (! Schema::hasColumn('courses', 'time_from')) {
                $table->time('time_from')->nullable()->after('days_count');
            }

            if (! Schema::hasColumn('courses', 'time_to')) {
                $table->time('time_to')->nullable()->after('time_from');
            }

            if (! Schema::hasColumn('courses', 'delivery_type')) {
                $table->string('delivery_type')->nullable()->after('time_to');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'delivery_type')) {
                $table->dropColumn('delivery_type');
            }

            if (Schema::hasColumn('courses', 'time_to')) {
                $table->dropColumn('time_to');
            }

            if (Schema::hasColumn('courses', 'time_from')) {
                $table->dropColumn('time_from');
            }

            if (Schema::hasColumn('courses', 'days_count')) {
                $table->dropColumn('days_count');
            }

            if (Schema::hasColumn('courses', 'duration_group')) {
                $table->dropIndex(['duration_group']);
                $table->dropColumn('duration_group');
            }

            if (Schema::hasColumn('courses', 'duration_hours')) {
                $table->dropColumn('duration_hours');
            }
        });
    }
};
