<?php

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Filament\Admin\Resources\ShareLinks\Tables\ShareLinksTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShareLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'shareLinks';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.employee_share_links');
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
                TextColumn::make('target_type')
                    ->label(__('admin.labels.target_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'course' => 'info',
                        'bundle' => 'warning',
                        'category' => 'success',
                        'homepage' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => __('admin.options.'.$state)),
                TextColumn::make('target_label')->label(__('admin.labels.target'))->placeholder('—'),
                TextColumn::make('full_url')
                    ->label(__('admin.labels.full_url'))
                    ->copyable()
                    ->copyMessage(__('admin.messages.copied'))
                    ->limit(55)
                    ->tooltip(fn ($record): string => $record->full_url),
                ShareLinksTable::urlCopyColumn(),
                TextColumn::make('createdBy.name')->label(__('admin.labels.created_by'))->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                ShareLinksTable::targetFilter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.share_links.heading'))
            ->emptyStateDescription(__('admin.empty_states.share_links.description'));
    }
}
