<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Support\PermissionModuleGrouper;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'admin';

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterCreate(): void
    {
        /** @var Role $record */
        $record = $this->record;

        $record->syncPermissions($this->extractSelectedPermissions());
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
