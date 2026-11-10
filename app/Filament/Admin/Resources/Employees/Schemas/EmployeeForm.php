<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.details'))
                    ->schema([
                        TextInput::make('employee_code')
                            ->label(__('admin.labels.employee_code'))
                            ->placeholder(__('admin.helpers.auto_generated'))
                            ->maxLength(60)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label(__('admin.labels.name'))
                            ->required()
                            ->maxLength(190),
                        TextInput::make('email')
                            ->label(__('admin.labels.email'))
                            ->email()
                            ->maxLength(190)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => __('admin.validation.email_unique'),
                            ]),
                        TextInput::make('phone')
                            ->label(__('admin.labels.phone'))
                            ->maxLength(60),
                        TextInput::make('job_title')
                            ->label(__('admin.labels.job_title'))
                            ->maxLength(190),
                        Select::make('role')
                            ->label(__('admin.labels.role'))
                            ->options(collect(EmployeeRole::cases())->mapWithKeys(
                                fn (EmployeeRole $r) => [$r->value => __('admin.options.'.$r->value)]
                            ))
                            ->searchable(),
                        TextInput::make('commission_rate')
                            ->label(__('admin.labels.commission_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0),
                        Select::make('user_id')
                            ->label(__('admin.labels.user'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        TextInput::make('password')
                            ->label(__('admin.labels.password'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(100)
                            ->hidden(fn ($get): bool => filled($get('user_id')))
                            ->required(fn ($get): bool => blank($get('user_id')))
                            ->helperText(__('admin.helpers.password_create_user'))
                            ->dehydrated(fn ($state): bool => filled($state)),
                        Select::make('department_id')
                            ->label(__('admin.labels.department'))
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('team_id')
                            ->label(__('admin.labels.team'))
                            ->relationship('team', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label(__('admin.labels.active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
