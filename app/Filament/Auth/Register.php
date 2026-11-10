<?php

namespace App\Filament\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\AuthUiEnhancerRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Register extends AuthUiEnhancerRegister
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        Config::set('auth.defaults.guard', 'admin');
        Config::set('permission.defaults.guard', 'admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (method_exists($user, 'assignRole')) {
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
            $user->assignRole($role);
        }

        if (method_exists($user, 'syncPermissions')) {
            $permissions = Permission::query()
                ->where('guard_name', 'admin')
                ->get();
            $user->syncPermissions($permissions);
        }

        return $user;
    }
}
