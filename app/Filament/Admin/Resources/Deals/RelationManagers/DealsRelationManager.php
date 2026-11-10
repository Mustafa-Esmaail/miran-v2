<?php

namespace App\Filament\Admin\Resources\Deals\RelationManagers;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.deals');
    }

    public function form(Schema $schema): Schema
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
                        ->required()
                        ->visible(fn (): bool => $this->getOwnerRecord() instanceof ServiceRequest),

                    Select::make('service_request_id')
                        ->label(__('admin.labels.lead'))
                        ->options(fn () => ServiceRequest::query()
                            ->orderByDesc('id')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' — '.($r->name ?: $r->email)]))
                        ->searchable()
                        ->nullable()
                        ->hidden(fn (): bool => $this->getOwnerRecord() instanceof ServiceRequest),

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
                        ->rows(2)
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
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('admin.labels.title'))->limit(50),
                TextColumn::make('stage')
                    ->label(__('admin.labels.stage'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'closed_won' => 'success',
                        'closed_lost' => 'danger',
                        'negotiation' => 'warning',
                        'proposal' => 'info',
                        'qualification' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('value')
                    ->label(__('admin.labels.deal_value'))
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2).' '.__('admin.labels.sar')),
                TextColumn::make('employee.name')->label(__('admin.labels.assigned_to'))->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create_deal')),
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.deals.heading'))
            ->emptyStateDescription(__('admin.empty_states.deals.description'));
    }
}
