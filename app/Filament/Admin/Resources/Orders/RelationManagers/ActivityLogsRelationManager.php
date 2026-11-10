<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Models\OrderActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_activity_logs');
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
            TextColumn::make('diff')
                ->label(__('admin.labels.changes'))
                ->wrap()
                ->state(function (OrderActivityLog $record): string {
                    $diff = $record->diff;

                    if (! $diff) {
                        return '—';
                    }

                    return collect($diff)
                        ->map(fn (array $change, string $field): string => $field.': '.($change['old'] ?? '—').' → '.($change['new'] ?? '—'))
                        ->implode("\n");
                }),
            TextColumn::make('notes')
                ->label(__('admin.labels.notes'))
                ->wrap()
                ->placeholder('—'),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_activity_logs.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_activity_logs.description'));
    }
}
