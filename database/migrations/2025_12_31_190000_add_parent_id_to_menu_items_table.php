<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete()->after('url');
            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['menu_id', 'parent_id', 'sort_order']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
