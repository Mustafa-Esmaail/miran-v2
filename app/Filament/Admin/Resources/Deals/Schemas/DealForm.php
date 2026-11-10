<?php

namespace App\Filament\Admin\Resources\Deals\Schemas;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.labels.title'))
                        ->required()
                        ->maxLength(190)
                        ->columnSpanFull(),

                    Select::make('company_id')
                        ->label(__('admin.labels.company'))
                        ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('service_request_id')
                        ->label(__('admin.labels.lead'))
                        ->options(fn () => ServiceRequest::query()
                            ->orderByDesc('id')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' — '.($r->name ?: $r->email)]))
                        ->searchable()
                        ->nullable(),

                    Select::make('employee_id')
                        ->label(__('admin.labels.assigned_to'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    DatePicker::make('expected_close_date')
                        ->label(__('admin.labels.expected_close_date')),

                    Textarea::make('notes')
                        ->label(__('admin.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('admin.sections.status'))
                ->schema([
                    Select::make('stage')
                        ->label(__('admin.labels.stage'))
                        ->options(collect(DealStage::cases())->mapWithKeys(
                            fn (DealStage $s) => [$s->value => __('admin.options.'.$s->value)]
                        ))
                        ->required()
                        ->default(DealStage::Prospecting->value),

                    TextInput::make('probability')
                        ->label(__('admin.labels.probability'))
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0),

                    TextInput::make('value')
                        ->label(__('admin.labels.deal_value'))
                        ->numeric()
                        ->suffix(__('admin.labels.sar'))
                        ->default(0),

                    Textarea::make('lost_reason')
                        ->label(__('admin.labels.lost_reason'))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
