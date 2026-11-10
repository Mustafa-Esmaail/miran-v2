<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('courses', 'delivery_type')) {
            return;
        }

        $mappings = [
            'Online' => 'online',
            'Offline' => 'offline',
            'Hybrid' => 'hybrid',
            'RemoteLive' => 'remote_live',
        ];

        foreach ($mappings as $from => $to) {
            DB::table('courses')
                ->where('delivery_type', $from)
                ->update(['delivery_type' => $to]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('courses', 'delivery_type')) {
            return;
        }

        $mappings = [
            'online' => 'Online',
            'offline' => 'Offline',
            'hybrid' => 'Hybrid',
            'remote_live' => 'RemoteLive',
        ];

        foreach ($mappings as $from => $to) {
            DB::table('courses')
                ->where('delivery_type', $from)
                ->update(['delivery_type' => $to]);
        }
    }
};
