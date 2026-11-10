<?php

namespace App\Filament\Admin\Resources\Deals\RelationManagers;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use App\Models\CrmTask;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'crmTasks';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.crm_tasks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label(__('admin.labels.title'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),
            Select::make('priority')
                ->label(__('admin.labels.priority'))
                ->options(collect(CrmTaskPriority::cases())->mapWithKeys(
                    fn (CrmTaskPriority $p) => [$p->value => __('admin.options.'.$p->value)]
                ))
                ->default(CrmTaskPriority::Normal->value)
                ->required(),
            Select::make('status')
                ->label(__('admin.labels.status'))
                ->options(collect(CrmTaskStatus::cases())->mapWithKeys(
                    fn (CrmTaskStatus $status) => [$status->value => __('admin.options.'.$status->value)]
                ))
                ->default(CrmTaskStatus::Todo->value)
                ->required(),
            DateTimePicker::make('due_at')
                ->label(__('admin.labels.due_at'))
                ->seconds(false),
            Select::make('employee_id')
                ->label(__('admin.labels.assigned_to'))
                ->options(fn () => Employee::query()
                    ->whereNotNull('user_id')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                ->searchable()
                ->preload(),
            Textarea::make('notes')
                ->label(__('admin.labels.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('admin.labels.title'))->searchable()->limit(50),
                TextColumn::make('priority')
                    ->label(__('admin.labels.priority'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'todo' => 'gray',
                        'in_progress' => 'warning',
                        'done' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('due_at')
                    ->label(__('admin.labels.due_at'))
                    ->dateTime('Y-m-d H:i')
                    ->color(fn (CrmTask $record): string => ! $record->is_done && $record->due_at?->isPast() ? 'danger' : 'gray')
                    ->sortable(),
                IconColumn::make('is_done')->label(__('admin.labels.is_done'))->boolean(),
                TextColumn::make('employee.name')->label(__('admin.labels.assigned_to'))->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.add_task')),
            ])
            ->recordActions([
                Action::make('mark_done')
                    ->label(__('admin.actions.mark_done'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CrmTask $record) => ! $record->is_done)
                    ->action(fn (CrmTask $record) => $record->update([
                        'status' => CrmTaskStatus::Done,
                        'is_done' => true,
                        'completed_at' => now(),
                    ])),
                EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->defaultSort('due_at', 'asc')
            ->emptyStateHeading(__('admin.empty_states.crm_tasks.heading'))
            ->emptyStateDescription(__('admin.empty_states.crm_tasks.description'));
    }
}
