<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReferralSourceSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::query()->updateOrCreate(
            ['code' => 'CC'],
            ['name' => 'Customer Care', 'is_active' => true],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'ref.agent@example.com'],
            [
                'name'     => 'Referral Agent',
                'password' => Hash::make('password'),
                'status'   => User::STATUS_ACTIVE,
            ],
        );

        $employee = Employee::query()->updateOrCreate(
            ['email' => 'ref.agent@example.com'],
            [
                'user_id'       => $user->id,
                'department_id' => $department->id,
                'name'          => 'Referral Agent',
                'is_active'     => true,
            ],
        );

        // Refresh so observer-generated employee_code is visible
        $employee->refresh();

        $code = $employee->employee_code ?? 'CC-'.$employee->id;

        $this->command->info('');
        $this->command->info('Referral source seeder done.');
        $this->command->info("Employee code : {$code}");
        $this->command->info("Test URL      : /?ref={$code}");
        $this->command->info('');
        $this->command->info('Open the URL above, browse to checkout, place an order,');
        $this->command->info("then check the order's 'Referred by' column in admin.");
        $this->command->info('');
    }
}
