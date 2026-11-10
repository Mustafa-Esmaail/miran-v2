<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NoticesRelationManager extends RelationManager
{
    protected static string $relationship = 'notices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_notices');
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')
                ->label(__('admin.labels.created_at'))
                ->dateTime('Y-m-d H:i')
                ->sortable(),
            TextColumn::make('user.name')
                ->label(__('admin.labels.performed_by'))
                ->placeholder('—'),
            TextColumn::make('note')
                ->label(__('admin.labels.notice'))
                ->wrap(),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_notices.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_notices.description'));
    }
}
