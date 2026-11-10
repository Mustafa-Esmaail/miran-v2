<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Support\PermissionModuleGrouper;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    private const PROTECTED_ROLES = ['super-admin'];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Role $role */
        $role = $this->record;

        if (in_array($role->name, self::PROTECTED_ROLES, true) && ! auth('admin')->user()?->hasRole('super-admin')) {
            Notification::make()
                ->warning()
                ->title(__('admin.notifications.protected_role_cannot_edit'))
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function (): bool {
                    /** @var Role $role */
                    $role = $this->record;

                    return ! in_array($role->name, self::PROTECTED_ROLES, true);
                }),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->record;
        $rolePermissions = $role->permissions()->pluck('name');

        foreach (PermissionModuleGrouper::groupedPermissions() as $modules) {
            foreach ($modules as $module => $options) {
                $data['permissions_'.$module] = $rolePermissions->intersect(array_keys($options))->values()->all();
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['name']);
    }

    protected function afterSave(): void
    {
        /** @var Role $role */
        $role = $this->record;

        $role->syncPermissions($this->extractSelectedPermissions());
    }

    /**
     * @return array<int, string>
     */
    protected function extractSelectedPermissions(): array
    {
        $state = $this->form->getState();
        $permissions = [];

        foreach (PermissionModuleGrouper::allFieldNames() as $fieldName) {
            $permissions = [...$permissions, ...($state[$fieldName] ?? [])];
        }

        return array_values(array_unique($permissions));
    }
}
