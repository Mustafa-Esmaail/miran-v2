<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('status');
            }

            if (! Schema::hasColumn('courses', 'old_price')) {
                $table->decimal('old_price', 10, 2)->nullable()->after('price');
            }

            if (! Schema::hasColumn('courses', 'image_path')) {
                $table->string('image_path')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('courses', 'start_date')) {
                $table->date('start_date')->nullable()->after('old_price');
            }

            if (! Schema::hasColumn('courses', 'source_url')) {
                $table->string('source_url')->nullable()->unique()->after('start_date');
            }

            if (! Schema::hasColumn('courses', 'sku')) {
                $table->string('sku')->nullable()->after('source_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'sku')) {
                $table->dropColumn('sku');
            }

            if (Schema::hasColumn('courses', 'source_url')) {
                $table->dropUnique(['source_url']);
                $table->dropColumn('source_url');
            }

            if (Schema::hasColumn('courses', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('courses', 'image_path')) {
                $table->dropColumn('image_path');
            }

            if (Schema::hasColumn('courses', 'old_price')) {
                $table->dropColumn('old_price');
            }

            if (Schema::hasColumn('courses', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
