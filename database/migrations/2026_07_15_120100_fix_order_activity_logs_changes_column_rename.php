<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New migration (fresh timestamp) so this actually runs in environments
     * that already recorded 2026_07_15_120000_... as applied before its fix
     * was added — Laravel only tracks migrations by filename, so editing an
     * already-run file never re-executes it there.
     *
     * Same defensive backfill: environments that ran
     * 2026_07_13_160000_create_order_activity_logs_table before its
     * `changes` column was renamed to `diff` still have the old `changes`
     * column (NOT NULL, no default), which breaks every insert since the
     * app only ever writes to `diff`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('order_activity_logs')) {
            Schema::create('order_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('diff');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        if (Schema::hasColumn('order_activity_logs', 'diff')) {
            return;
        }

        if (Schema::hasColumn('order_activity_logs', 'changes')) {
            Schema::table('order_activity_logs', function (Blueprint $table) {
                $table->renameColumn('changes', 'diff');
            });

            return;
        }

        Schema::table('order_activity_logs', function (Blueprint $table) {
            $table->json('diff')->nullable()->after('user_id');
        });
    }

    /**
     * No-op: this migration only backfills a missing table/column for
     * environments where the original migration never applied correctly.
     * Reversing it could drop the table (and any rows since logged) in
     * environments where it was in fact needed.
     */
    public function down(): void
    {
        //
    }
};
