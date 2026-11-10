<?php

namespace App\Filament\Admin\Resources\Bundles\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.bundle_courses');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('category.name')
                ->label(__('admin.labels.category'))
                ->formatStateUsing(fn ($state, $record) => $record->category?->getTranslation('name', app()->getLocale()) ?? '-'),
            TextColumn::make('price')
                ->label(__('admin.labels.price'))
                ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' SAR')
                ->sortable(),
        ])->headerActions([
            AttachAction::make()
                ->label(__('admin.labels.attach_course'))
                ->recordTitle(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                ->preloadRecordSelect(),
        ])->actions([
            DetachAction::make()->label(__('admin.actions.detach')),
        ])
            ->emptyStateHeading(__('admin.empty_states.bundle_courses.heading'))
            ->emptyStateDescription(__('admin.empty_states.bundle_courses.description'));
    }
}
