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
        $this->moveRole('trainer', 'instructor');

        DB::table('chat_room_participants')
            ->where('role_in_room', 'trainer')
            ->update(['role_in_room' => 'instructor']);

        $this->clearPermissionCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->copyRole('instructor', 'trainer');

        DB::table('chat_room_participants')
            ->where('role_in_room', 'instructor')
            ->update(['role_in_room' => 'trainer']);

        $this->clearPermissionCache();
    }

    private function moveRole(string $fromRoleName, string $toRoleName): void
    {
        foreach ($this->rolesNamed($fromRoleName) as $fromRole) {
            $toRole = $this->roleNamed($toRoleName, $fromRole->guard_name);

            if (! $toRole) {
                DB::table($this->rolesTable())
                    ->where('id', $fromRole->id)
                    ->update([
                        'name' => $toRoleName,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            $this->copyRoleAssignments((int) $fromRole->id, (int) $toRole->id);
            $this->deleteRole((int) $fromRole->id);
        }
    }

    private function copyRole(string $fromRoleName, string $toRoleName): void
    {
        foreach ($this->rolesNamed($fromRoleName) as $fromRole) {
            $toRole = $this->roleNamed($toRoleName, $fromRole->guard_name);
            $toRoleId = $toRole
                ? (int) $toRole->id
                : $this->createRole($toRoleName, $fromRole->guard_name);

            $this->copyRoleAssignments((int) $fromRole->id, $toRoleId);
        }
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

    private function roleNamed(string $roleName, string $guardName): ?object
    {
        return DB::table($this->rolesTable())
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->first();
    }

    private function createRole(string $roleName, string $guardName): int
    {
        return (int) DB::table($this->rolesTable())->insertGetId([
            'name' => $roleName,
            'guard_name' => $guardName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function copyRoleAssignments(int $fromRoleId, int $toRoleId): void
    {
        $this->copyRolePermissions($fromRoleId, $toRoleId);
        $this->copyModelRoles($fromRoleId, $toRoleId);
    }

    private function copyRolePermissions(int $fromRoleId, int $toRoleId): void
    {
        $permissionPivotKey = $this->permissionPivotKey();
        $rolePivotKey = $this->rolePivotKey();

        DB::table($this->roleHasPermissionsTable())
            ->where($rolePivotKey, $fromRoleId)
            ->pluck($permissionPivotKey)
            ->each(function (int $permissionId) use ($permissionPivotKey, $rolePivotKey, $toRoleId): void {
                $exists = DB::table($this->roleHasPermissionsTable())
                    ->where($permissionPivotKey, $permissionId)
                    ->where($rolePivotKey, $toRoleId)
                    ->exists();

                if (! $exists) {
                    DB::table($this->roleHasPermissionsTable())->insert([
                        $permissionPivotKey => $permissionId,
                        $rolePivotKey => $toRoleId,
                    ]);
                }
            });
    }

    private function copyModelRoles(int $fromRoleId, int $toRoleId): void
    {
        $modelMorphKey = $this->modelMorphKey();
        $rolePivotKey = $this->rolePivotKey();

        DB::table($this->modelHasRolesTable())
            ->where($rolePivotKey, $fromRoleId)
            ->get()
            ->each(function (object $assignment) use ($modelMorphKey, $rolePivotKey, $toRoleId): void {
                $exists = DB::table($this->modelHasRolesTable())
                    ->where($rolePivotKey, $toRoleId)
                    ->where($modelMorphKey, $assignment->{$modelMorphKey})
                    ->where('model_type', $assignment->model_type)
                    ->exists();

                if (! $exists) {
                    DB::table($this->modelHasRolesTable())->insert([
                        $rolePivotKey => $toRoleId,
                        $modelMorphKey => $assignment->{$modelMorphKey},
                        'model_type' => $assignment->model_type,
                    ]);
                }
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

    private function permissionPivotKey(): string
    {
        return config('permission.column_names.permission_pivot_key') ?? 'permission_id';
    }

    private function modelMorphKey(): string
    {
        return config('permission.column_names.model_morph_key', 'model_id');
    }

    private function clearPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
