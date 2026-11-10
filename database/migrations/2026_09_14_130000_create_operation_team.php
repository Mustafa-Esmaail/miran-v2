<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $operationsDepartmentId = DB::table('departments')->where('code', 'DEPT-OPS')->value('id');

        if (! $operationsDepartmentId) {
            return;
        }

        DB::table('teams')->insert([
            'department_id' => $operationsDepartmentId,
            'name' => 'Operation',
            'code' => 'TEAM-OPS-OPERATION',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('teams')->where('code', 'TEAM-OPS-OPERATION')->delete();
    }
};
