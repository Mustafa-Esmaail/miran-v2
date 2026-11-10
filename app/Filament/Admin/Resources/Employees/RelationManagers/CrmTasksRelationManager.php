<?php

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'crmTasks';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.employee_tasks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')->label(__('admin.labels.title'))->searchable()->limit(50),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof CrmTaskStatus ? $state->value : (string) $state) {
                        'todo' => 'gray',
                        'in_progress' => 'warning',
                        'done' => 'success',
                        default => 'secondary',
                    }),
                TextColumn::make('priority')
                    ->label(__('admin.labels.priority'))
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof CrmTaskPriority ? $state->value : (string) $state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('entity_type')
                    ->label(__('admin.labels.entity'))
                    ->formatStateUsing(fn ($state) => class_basename($state ?? ''))
                    ->badge()
                    ->toggleable(),
                IconColumn::make('is_done')->label(__('admin.labels.done'))->boolean(),
                TextColumn::make('due_at')->label(__('admin.labels.due_at'))->dateTime('Y-m-d H:i')->sortable()->placeholder('—'),
                TextColumn::make('completed_at')->label(__('admin.labels.completed_at'))->dateTime('Y-m-d H:i')->toggleable()->placeholder('—'),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(CrmTaskStatus::cases())
                        ->mapWithKeys(fn (CrmTaskStatus $s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])
                        ->all()
                )->searchable(),
                SelectFilter::make('priority')->options(
                    collect(CrmTaskPriority::cases())
                        ->mapWithKeys(fn (CrmTaskPriority $p) => [$p->value => ucfirst($p->value)])
                        ->all()
                )->searchable(),
            ])
            ->defaultSort('due_at', 'asc')
            ->emptyStateHeading(__('admin.empty_states.crm_tasks.heading'))
            ->emptyStateDescription(__('admin.empty_states.crm_tasks.description'));
    }
}
