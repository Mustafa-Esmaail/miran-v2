<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fresh migration: the two prior fix attempts (120000, 120100) both
     * short-circuit with "if `diff` already exists, do nothing" — which is
     * wrong when the old `changes` column ALSO still exists (NOT NULL, no
     * default). That intermediate state happens when an environment ran the
     * very first defensive migration (which only added `diff` without
     * touching `changes`) before the rename fix was written. In that state,
     * every insert still fails because `changes` is required but never
     * populated by the app.
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

        $hasChanges = Schema::hasColumn('order_activity_logs', 'changes');
        $hasDiff = Schema::hasColumn('order_activity_logs', 'diff');

        if ($hasChanges && ! $hasDiff) {
            // Only the old column exists — rename it in place, preserving data.
            Schema::table('order_activity_logs', function (Blueprint $table) {
                $table->renameColumn('changes', 'diff');
            });

            return;
        }

        if ($hasChanges && $hasDiff) {
            // Both exist: `diff` is what the app actually writes to; the
            // leftover `changes` column blocks every insert since it's
            // NOT NULL with no default and never populated.
            Schema::table('order_activity_logs', function (Blueprint $table) {
                $table->dropColumn('changes');
            });

            return;
        }

        if (! $hasDiff) {
            Schema::table('order_activity_logs', function (Blueprint $table) {
                $table->json('diff')->nullable()->after('user_id');
            });
        }
    }

    /**
     * No-op: this migration only backfills/repairs a broken schema state in
     * environments where earlier migrations didn't fully apply. Reversing it
     * could drop the table or corrupt data in environments where it was in
     * fact needed.
     */
    public function down(): void
    {
        //
    }
};
