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
        foreach ($this->rolesNamed('instructor_old') as $oldRole) {
            $this->forceDeleteRolesNamed('instructor', $oldRole->guard_name);

            DB::table($this->rolesTable())
                ->where('id', $oldRole->id)
                ->update([
                    'name' => 'instructor',
                    'updated_at' => now(),
                ]);
        }

        $this->clearPermissionCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best effort only: the duplicate 'instructor' role deleted in up()
        // cannot be restored. This just renames the role back.
        foreach ($this->rolesNamed('instructor') as $role) {
            DB::table($this->rolesTable())
                ->where('id', $role->id)
                ->update([
                    'name' => 'instructor_old',
                    'updated_at' => now(),
                ]);
        }

        $this->clearPermissionCache();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rolesNamed(string $roleName): \Illuminate\Support\Collection
    {
        return DB::table($this->rolesTable())
            ->where('name', $roleName)
            ->orderBy('id')
            ->get();
    }

    private function forceDeleteRolesNamed(string $roleName, string $guardName): void
    {
        DB::table($this->rolesTable())
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->get()
            ->each(function (object $role): void {
                $this->deleteRole((int) $role->id);
            });
    }

    private function deleteRole(int $roleId): void
    {
        DB::table($this->roleHasPermissionsTable())
            ->where($this->rolePivotKey(), $roleId)
            ->delete();

        DB::table($this->modelHasRolesTable())
            ->where($this->rolePivotKey(), $roleId)
            ->delete();

        DB::table($this->rolesTable())
            ->where('id', $roleId)
            ->delete();
    }

    private function rolesTable(): string
    {
        return config('permission.table_names.roles', 'roles');
    }

    private function roleHasPermissionsTable(): string
    {
        return config('permission.table_names.role_has_permissions', 'role_has_permissions');
    }

    private function modelHasRolesTable(): string
    {
        return config('permission.table_names.model_has_roles', 'model_has_roles');
    }

    private function rolePivotKey(): string
    {
        return config('permission.column_names.role_pivot_key') ?? 'role_id';
    }

    private function clearPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
