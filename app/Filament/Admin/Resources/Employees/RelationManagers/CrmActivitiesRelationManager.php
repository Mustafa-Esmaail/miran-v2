<?php

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'crmActivities';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.employee_activities');
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
                TextColumn::make('type')
                    ->label(__('admin.labels.type'))
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof CrmActivityType ? $state->value : (string) $state) {
                        'call' => 'success',
                        'email' => 'info',
                        'whatsapp' => 'success',
                        'meeting' => 'warning',
                        'note' => 'gray',
                        'visit' => 'primary',
                        default => 'secondary',
                    }),
                TextColumn::make('subject')->label(__('admin.labels.subject'))->searchable()->limit(40),
                TextColumn::make('entity_type')
                    ->label(__('admin.labels.entity'))
                    ->formatStateUsing(fn ($state) => class_basename($state ?? ''))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('outcome')
                    ->label(__('admin.labels.outcome'))
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof CrmActivityOutcome ? $state->value : (string) $state) {
                        'reached' => 'success',
                        'interested' => 'success',
                        'no_answer' => 'warning',
                        'not_interested' => 'danger',
                        'callback' => 'info',
                        default => 'secondary',
                    })
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('scheduled_at')->label(__('admin.labels.scheduled_at'))->dateTime('Y-m-d H:i')->toggleable()->placeholder('—'),
                TextColumn::make('completed_at')->label(__('admin.labels.completed_at'))->dateTime('Y-m-d H:i')->toggleable()->placeholder('—'),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(
                    collect(CrmActivityType::cases())
                        ->mapWithKeys(fn (CrmActivityType $t) => [$t->value => ucfirst($t->value)])
                        ->all()
                )->searchable(),
                SelectFilter::make('outcome')->options(
                    collect(CrmActivityOutcome::cases())
                        ->mapWithKeys(fn (CrmActivityOutcome $o) => [$o->value => ucfirst(str_replace('_', ' ', $o->value))])
                        ->all()
                )->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.crm_activities.heading'))
            ->emptyStateDescription(__('admin.empty_states.crm_activities.description'));
    }
}
