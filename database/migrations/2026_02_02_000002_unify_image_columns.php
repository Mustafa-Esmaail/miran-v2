<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Courses table
        if (! Schema::hasColumn('courses', 'image_path')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('image_path')->nullable();
            });
        }

        if (Schema::hasTable('courses') && (Schema::hasColumn('courses', 'thumbnail_path') || Schema::hasColumn('courses', 'image_url'))) {
            DB::statement('UPDATE courses SET image_path = COALESCE(image_path, thumbnail_path, image_url)');
        }

        if (Schema::hasColumn('courses', 'thumbnail_path')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('thumbnail_path');
            });
        }

        if (Schema::hasColumn('courses', 'image_url')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }

        // Bundles table
        if (! Schema::hasColumn('bundles', 'image_path')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->string('image_path')->nullable();
            });
        }

        if (Schema::hasTable('bundles') && (Schema::hasColumn('bundles', 'thumbnail_path') || Schema::hasColumn('bundles', 'image_url'))) {
            DB::statement('UPDATE bundles SET image_path = COALESCE(image_path, thumbnail_path, image_url)');
        }

        if (Schema::hasColumn('bundles', 'thumbnail_path')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->dropColumn('thumbnail_path');
            });
        }

        if (Schema::hasColumn('bundles', 'image_url')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }
    }

    public function down(): void
    {
        // Courses: restore legacy columns and copy back
        if (! Schema::hasColumn('courses', 'thumbnail_path')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('thumbnail_path')->nullable();
            });
        }

        if (! Schema::hasColumn('courses', 'image_url')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('image_url')->nullable();
            });
        }

        if (Schema::hasColumn('courses', 'image_path')) {
            DB::statement('UPDATE courses SET thumbnail_path = COALESCE(thumbnail_path, image_path), image_url = COALESCE(image_url, image_path)');
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }

        // Bundles: restore legacy columns and copy back
        if (! Schema::hasColumn('bundles', 'thumbnail_path')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->string('thumbnail_path')->nullable();
            });
        }

        if (! Schema::hasColumn('bundles', 'image_url')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->string('image_url')->nullable();
            });
        }

        if (Schema::hasColumn('bundles', 'image_path')) {
            DB::statement('UPDATE bundles SET thumbnail_path = COALESCE(thumbnail_path, image_path), image_url = COALESCE(image_url, image_path)');
            Schema::table('bundles', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
};
