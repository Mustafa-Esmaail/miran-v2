<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Models\Employee;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'crmActivities';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.crm_activities');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('type')
                ->label(__('admin.labels.type'))
                ->options(collect(CrmActivityType::cases())->mapWithKeys(
                    fn (CrmActivityType $t) => [$t->value => __('admin.options.'.$t->value)]
                ))
                ->required(),
            Select::make('outcome')
                ->label(__('admin.labels.outcome'))
                ->options(collect(CrmActivityOutcome::cases())->mapWithKeys(
                    fn (CrmActivityOutcome $o) => [$o->value => __('admin.options.'.$o->value)]
                ))
                ->nullable(),
            TextInput::make('subject')
                ->label(__('admin.labels.subject'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label(__('admin.labels.body'))
                ->rows(3)
                ->columnSpanFull(),
            DateTimePicker::make('scheduled_at')
                ->label(__('admin.labels.scheduled_at'))
                ->seconds(false),
            DateTimePicker::make('completed_at')
                ->label(__('admin.labels.completed_at'))
                ->seconds(false),
            Select::make('employee_id')
                ->label(__('admin.labels.logged_by'))
                ->options(fn () => Employee::query()
                    ->whereNotNull('user_id')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                ->searchable()
                ->preload(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('type')
                ->label(__('admin.labels.type'))
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                ->color(fn ($state) => match ($state?->value) {
                    'call' => 'info',
                    'whatsapp' => 'success',
                    'email' => 'primary',
                    'meeting' => 'warning',
                    'visit' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('subject')->label(__('admin.labels.subject'))->limit(50),
            TextColumn::make('outcome')
                ->label(__('admin.labels.outcome'))
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                ->color(fn ($state) => match ($state?->value) {
                    'interested' => 'success',
                    'reached', 'callback' => 'info',
                    'no_answer' => 'warning',
                    'not_interested' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('employee.name')->label(__('admin.labels.logged_by'))->toggleable(),
            TextColumn::make('scheduled_at')->label(__('admin.labels.scheduled_at'))->dateTime('Y-m-d H:i')->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.log_activity'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_at'] = now();

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.crm_activities.heading'))
            ->emptyStateDescription(__('admin.empty_states.crm_activities.description'));
    }
}
