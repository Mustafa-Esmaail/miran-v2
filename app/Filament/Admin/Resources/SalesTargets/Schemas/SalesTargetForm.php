<?php

namespace App\Filament\Admin\Resources\SalesTargets\Schemas;

use App\Enums\SalesTargetPeriodType;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('employee_id')
                        ->label(__('admin.labels.sales_person'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->name.($e->team?->name ? ' — '.$e->team->name : '')])
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('period_type')
                        ->label(__('admin.labels.period_type'))
                        ->options([
                            SalesTargetPeriodType::Monthly->value => __('admin.options.monthly'),
                            SalesTargetPeriodType::Quarterly->value => __('admin.options.quarterly'),
                        ])
                        ->default(SalesTargetPeriodType::Monthly->value)
                        ->required(),
                    TextInput::make('period')
                        ->label(__('admin.labels.period'))
                        ->placeholder('2026-04 or 2026-Q2')
                        ->helperText(__('admin.helpers.sales_target_period'))
                        ->default(now()->format('Y-m'))
                        ->required()
                        ->maxLength(10),
                    TextInput::make('target_revenue')
                        ->label(__('admin.labels.target_revenue'))
                        ->numeric()
                        ->prefix('SAR')
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    TextInput::make('target_leads')
                        ->label(__('admin.labels.target_leads'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('admin.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
