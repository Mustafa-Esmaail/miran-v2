<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_code', 60)->nullable()->unique()->after('id');
        });

        // Backfill existing employees
        $employees = DB::table('employees')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select('employees.id', 'departments.code as dept_code')
            ->get();

        foreach ($employees as $emp) {
            $prefix = filled($emp->dept_code) ? $emp->dept_code : 'EMP';
            DB::table('employees')
                ->where('id', $emp->id)
                ->update(['employee_code' => $prefix . '-' . $emp->id]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employee_code');
        });
    }
};
