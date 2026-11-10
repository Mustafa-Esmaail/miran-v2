<?php

namespace App\Filament\Admin\Resources\SectionFields\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('section_id')->sortable(),
            TextColumn::make('key')->searchable(),
            TextColumn::make('type')->toggleable(),
            TextColumn::make('group')->toggleable(),
            TextColumn::make('sort_order')->sortable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            \Filament\Actions\CreateAction::make()->label(__('admin.actions.create')),
        ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.section_fields.heading'))
            ->emptyStateDescription(__('admin.empty_states.section_fields.description'));
    }
}
