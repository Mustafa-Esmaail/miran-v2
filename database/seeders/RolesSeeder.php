<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'visitor',
            'student',
            'instructor',
            'admin',
            'super-admin',
            'crm_manager',
            'department_manager',
            'team_lead',
            'sales_agent',
            'support_agent',
            'quality_auditor',
            'accountant',
            'marketing',
            'course_manager',
            'training_coordinator',
            'content_editor',
            'content_manager',
        ] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']);
        }
    }
}
