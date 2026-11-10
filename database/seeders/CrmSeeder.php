<?php

namespace Database\Seeders;

use App\Enums\ServiceRequestActivityType;
use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            ['name' => 'Sales', 'code' => 'DEPT-SALES'],
            ['name' => 'Support', 'code' => 'DEPT-SUPPORT'],
            ['name' => 'Operations', 'code' => 'DEPT-OPS'],
        ])->map(fn (array $attributes): Department => Department::query()->updateOrCreate(
            ['code' => $attributes['code']],
            ['name' => $attributes['name'], 'is_active' => true],
        ));

        $teams = collect([
            ['department_code' => 'DEPT-SALES', 'name' => 'Inside Sales', 'code' => 'TEAM-SALES-INSIDE'],
            ['department_code' => 'DEPT-SALES', 'name' => 'Corporate Sales', 'code' => 'TEAM-SALES-CORP'],
            ['department_code' => 'DEPT-SUPPORT', 'name' => 'Customer Success', 'code' => 'TEAM-SUPPORT-CS'],
            ['department_code' => 'DEPT-SUPPORT', 'name' => 'Technical Support', 'code' => 'TEAM-SUPPORT-TECH'],
            ['department_code' => 'DEPT-OPS', 'name' => 'Back Office', 'code' => 'TEAM-OPS-BACK'],
        ])->map(function (array $attributes) use ($departments): Team {
            $department = $departments->firstWhere('code', $attributes['department_code']);

            return Team::query()->updateOrCreate(
                ['code' => $attributes['code']],
                [
                    'department_id' => $department?->id,
                    'name' => $attributes['name'],
                    'is_active' => true,
                ],
            );
        });

        $employeeBlueprints = [
            [
                'name' => 'Sales Lead',
                'email' => 'crm.sales.lead@example.com',
                'job_title' => 'Sales Lead',
                'team_code' => 'TEAM-SALES-CORP',
            ],
            [
                'name' => 'Sales Agent',
                'email' => 'crm.sales.agent@example.com',
                'job_title' => 'Sales Specialist',
                'team_code' => 'TEAM-SALES-INSIDE',
            ],
            [
                'name' => 'Support Lead',
                'email' => 'crm.support.lead@example.com',
                'job_title' => 'Support Lead',
                'team_code' => 'TEAM-SUPPORT-CS',
            ],
            [
                'name' => 'Support Agent',
                'email' => 'crm.support.agent@example.com',
                'job_title' => 'Support Specialist',
                'team_code' => 'TEAM-SUPPORT-TECH',
            ],
            [
                'name' => 'Operations Agent',
                'email' => 'crm.ops.agent@example.com',
                'job_title' => 'Operations Coordinator',
                'team_code' => 'TEAM-OPS-BACK',
            ],
        ];

        $employees = collect($employeeBlueprints)->map(function (array $blueprint) use ($teams): Employee {
            $user = User::query()->updateOrCreate(
                ['email' => $blueprint['email']],
                [
                    'name' => $blueprint['name'],
                    'password' => Hash::make('password'),
                    'status' => User::STATUS_ACTIVE,
                    'phone' => fake()->numerify('05########'),
                ],
            );

            $team = $teams->firstWhere('code', $blueprint['team_code']);
            $departmentId = $team?->department_id;

            return Employee::query()->updateOrCreate(
                ['email' => $blueprint['email']],
                [
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                    'team_id' => $team?->id,
                    'name' => $blueprint['name'],
                    'phone' => $user->phone,
                    'job_title' => $blueprint['job_title'],
                    'is_active' => true,
                ],
            );
        });

        $salesOwner = $employees->firstWhere('email', 'crm.sales.lead@example.com');
        $assignedTo = $employees->firstWhere('email', 'crm.support.agent@example.com');
        $requestCustomer = User::query()->firstOrCreate(
            ['email' => 'crm.customer@example.com'],
            [
                'name' => 'CRM Customer',
                'password' => Hash::make('password'),
                'status' => User::STATUS_ACTIVE,
                'phone' => '0550000000',
            ],
        );

        $serviceRequest = ServiceRequest::query()->updateOrCreate(
            ['email' => $requestCustomer->email, 'phone' => $requestCustomer->phone],
            [
                'source' => ServiceRequestSource::Lead->value,
                'user_id' => $requestCustomer->id,
                'name' => $requestCustomer->name,
                'message' => 'Need full corporate training plan.',
                'status' => 'assigned',
                'team_id' => $teams->firstWhere('code', 'TEAM-SALES-CORP')?->id,
                'assigned_to' => $assignedTo?->user_id,
                'priority' => ServiceRequestPriority::High->value,
                'last_activity_at' => now(),
            ],
        );

        ServiceRequestActivity::query()->updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'type' => ServiceRequestActivityType::Created->value,
            ],
            [
                'actor_user_id' => $salesOwner?->user_id,
                'from_value' => null,
                'to_value' => 'new',
                'note' => 'Service request created from CRM seeder.',
                'meta' => ['seed' => 'crm'],
                'created_at' => now()->subMinutes(10),
            ],
        );

        ServiceRequestActivity::query()->updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'type' => ServiceRequestActivityType::AssignedChanged->value,
            ],
            [
                'actor_user_id' => $salesOwner?->user_id,
                'from_value' => null,
                'to_value' => (string) ($assignedTo?->user_id ?? ''),
                'note' => 'Assigned to support team.',
                'meta' => ['seed' => 'crm'],
                'created_at' => now()->subMinutes(5),
            ],
        );
    }
}
