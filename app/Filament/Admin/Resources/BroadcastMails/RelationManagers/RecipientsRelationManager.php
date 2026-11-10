<?php

namespace App\Filament\Admin\Resources\BroadcastMails\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.broadcast_mail_recipients');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label(__('admin.labels.name')),
            TextColumn::make('user.email')->label(__('admin.labels.email')),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function (string $state): string {
                    $key = 'admin.options.'.$state;

                    return __($key) !== $key ? __($key) : $state;
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'sent' => 'success',
                    'pending' => 'info',
                    'failed' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('sent_at')->label(__('admin.labels.sent_at'))->dateTime('Y-m-d H:i')->placeholder('—'),
            TextColumn::make('error_message')->label(__('admin.labels.error_message'))->limit(50)->placeholder('—')->toggleable(),
        ])
            ->defaultSort('id')
            ->emptyStateHeading(__('admin.empty_states.broadcast_mail_recipients.heading'));
    }
}
