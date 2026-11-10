<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('owner_team')->constrained('teams')->nullOnDelete();
        });

        $defaultTeamIdByDepartment = DB::table('teams')
            ->join('departments', 'departments.id', '=', 'teams.department_id')
            ->orderBy('teams.id')
            ->get(['teams.id', 'departments.name as department_name'])
            ->groupBy('department_name')
            ->map(fn ($teams) => $teams->first()->id);

        foreach ($defaultTeamIdByDepartment as $departmentName => $teamId) {
            DB::table('service_requests')
                ->whereRaw('lower(owner_team) = ?', [mb_strtolower((string) $departmentName)])
                ->update(['team_id' => $teamId]);
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['owner_team', 'status']);
            $table->dropColumn('owner_team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('owner_team')->nullable()->after('status');
        });

        DB::table('service_requests')
            ->join('teams', 'teams.id', '=', 'service_requests.team_id')
            ->join('departments', 'departments.id', '=', 'teams.department_id')
            ->update(['service_requests.owner_team' => DB::raw('lower(departments.name)')]);

        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('owner_team')->nullable(false)->change();
            $table->index(['owner_team', 'status']);
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
