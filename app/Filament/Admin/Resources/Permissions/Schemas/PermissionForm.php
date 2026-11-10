<?php

namespace App\Filament\Admin\Resources\Permissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.permission'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->helperText(__('admin.helpers.format_module_action_e_g_courses_viewany')),
                    Select::make('guard_name')
                        ->options(['web' => __('admin.options.web'), 'admin' => __('admin.options.admin')])
                        ->required()
                        ->default('web'),
                ])
                ->columns(2),
        ]);
    }
}
