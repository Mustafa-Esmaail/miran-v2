<?php

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Models\User;
use App\Notifications\EmployeeCredentialsNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    private ?string $plainPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainPassword = filled($data['password'] ?? null) ? $data['password'] : null;
        unset($data['password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $employee = $this->record;

        if ($employee->user_id || ! filled($employee->email) || ! filled($this->plainPassword)) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $employee->email],
            [
                'name' => $employee->name,
                'password' => Hash::make($this->plainPassword),
            ]
        );

        // update() fires EmployeeObserver::updated() → syncSpatieRole()
        $employee->update(['user_id' => $user->id]);

        try {
            $loginUrl = route('filament.admin.auth.login');
            $user->notify(new EmployeeCredentialsNotification($this->plainPassword, $loginUrl));
        } catch (\Throwable) {
            // mail failure is non-fatal
        }
    }
}
