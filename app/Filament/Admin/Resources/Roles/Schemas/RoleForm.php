<?php

namespace App\Filament\Admin\Resources\Roles\Schemas;

use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Support\PermissionModuleGrouper;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.role'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('guard_name', 'admin'))
                        ->disabled(fn (?Role $record): bool => $record !== null && in_array($record->name, RoleResource::PROTECTED_ROLES, true))
                        ->autofocus(),
                ]),
            static::buildPermissionsSection(),
        ]);
    }

    protected static function buildPermissionsSection(): Section
    {
        $grouped = PermissionModuleGrouper::groupedPermissions();

        /** @var array<string, array<int, string>> $allFieldOptions */
        $allFieldOptions = [];
        $groups = [];

        foreach ($grouped as $groupKey => $modules) {
            foreach ($modules as $module => $options) {
                $allFieldOptions['permissions_'.$module] = array_keys($options);
            }

            $groups[] = static::buildModuleGroupSection($groupKey, $modules);
        }

        return Section::make(__('admin.models.permissions'))
            ->columnSpanFull()
            ->schema([
                static::buildSelectAllToggle('select_all_permissions', __('admin.labels.select_all_permissions'), $allFieldOptions),
                ...$groups,
            ]);
    }

    /**
     * A toggle field that selects/clears every field in $fieldOptions. Used for
     * both the top-level "select all permissions" toggle and each navigation
     * group's own "select all in :module" toggle.
     *
     * @param  array<string, array<int, string>>  $fieldOptions  field name => option keys
     */
    protected static function buildSelectAllToggle(string $name, string $label, array $fieldOptions): Toggle
    {
        return Toggle::make($name)
            ->label($label)
            ->inline()
            ->live()
            ->dehydrated(false)
            ->extraFieldWrapperAttributes(['class' => 'flex justify-end'])
            ->afterStateHydrated(function (Toggle $component, Get $get) use ($fieldOptions): void {
                $component->state(static::areAllSelected($get, $fieldOptions));
            })
            ->afterStateUpdated(function (bool $state, Set $set) use ($fieldOptions): void {
                foreach ($fieldOptions as $fieldName => $options) {
                    $set($fieldName, $state ? $options : []);
                }
            });
    }

    /**
     * @param  array<string, array<int, string>>  $fieldOptions
     */
    protected static function areAllSelected(Get $get, array $fieldOptions): bool
    {
        foreach ($fieldOptions as $fieldName => $options) {
            if ($options === []) {
                continue;
            }

            /** @var array<int, string> $selected */
            $selected = $get($fieldName) ?? [];

            if (count($selected) !== count($options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, array<string, string>>  $modules
     */
    protected static function buildModuleGroupSection(string $groupKey, array $modules): Section
    {
        $sections = [];

        /** @var array<string, array<int, string>> $groupFieldOptions */
        $groupFieldOptions = [];

        foreach ($modules as $module => $options) {
            $groupFieldOptions['permissions_'.$module] = array_keys($options);

            $sections[] = Section::make(PermissionModuleGrouper::moduleLabel($module))
                ->compact()
                ->collapsible()
                ->collapsed()
                ->schema([
                    CheckboxList::make('permissions_'.$module)
                        ->hiddenLabel()
                        ->options($options)
                        ->bulkToggleable()
                        ->columns(2),
                ]);
        }

        $groupLabel = PermissionModuleGrouper::groupLabel($groupKey);

        return Section::make($groupLabel)
            ->compact()
            ->collapsible()
            ->schema([
                static::buildSelectAllToggle(
                    'select_all_'.$groupKey,
                    __('admin.labels.select_all_in', ['module' => $groupLabel]),
                    $groupFieldOptions,
                ),
                Grid::make(2)->schema($sections),
            ]);
    }
}
