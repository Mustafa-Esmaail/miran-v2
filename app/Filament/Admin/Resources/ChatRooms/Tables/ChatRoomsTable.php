<?php

namespace App\Filament\Admin\Resources\ChatRooms\Tables;

use App\Enums\ChatRoomType;
use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Models\ChatRoom;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChatRoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('admin.labels.type'))
                    ->badge()
                    ->color(fn (ChatRoomType $state): string => match ($state) {
                        ChatRoomType::CourseGroup => 'success',
                        ChatRoomType::Direct => 'info',
                    })
                    ->formatStateUsing(fn (ChatRoomType $state): string => match ($state) {
                        ChatRoomType::CourseGroup => __('admin.labels.chat_group'),
                        ChatRoomType::Direct => __('admin.labels.chat_direct'),
                    }),

                TextColumn::make('chat_name')
                    ->label(__('admin.labels.chat_name'))
                    ->getStateUsing(function (ChatRoom $record): string {
                        if ($record->type === ChatRoomType::Direct) {
                            $names = $record->participants
                                ->pluck('user.name')
                                ->filter()
                                ->implode(' <-> ');

                            return $names ?: '-';
                        }

                        return $record->course?->getTranslation('title', app()->getLocale()) ?? '-';
                    })
                    ->searchable(false)
                    ->limit(50),

                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.course_group'))
                    ->getStateUsing(function (ChatRoom $record): string {
                        if ($record->type === ChatRoomType::Direct) {
                            return '-';
                        }

                        return $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-';
                    })
                    ->limit(40),

                TextColumn::make('participants_count')
                    ->label(__('admin.labels.participants'))
                    ->counts('participants')
                    ->sortable(),

                TextColumn::make('messages_count')
                    ->label(__('admin.labels.messages'))
                    ->counts('messages')
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.labels.active') : __('admin.labels.archived')),

                TextColumn::make('last_message_at')
                    ->label(__('admin.labels.last_message_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.labels.type'))
                    ->options([
                        ChatRoomType::CourseGroup->value => __('admin.labels.chat_group'),
                        ChatRoomType::Direct->value => __('admin.labels.chat_direct'),
                    ])->searchable(),

                SelectFilter::make('is_active')
                    ->label(__('admin.labels.status'))
                    ->options([
                        '1' => __('admin.labels.active'),
                        '0' => __('admin.labels.archived'),
                    ])->searchable(),
            ])
            ->actions([
                Action::make('archive')
                    ->label(__('admin.actions.archive_room'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ChatRoom $record): bool => $record->is_active && auth()->user()?->hasAnyRole(['admin', 'super-admin']))
                    ->action(fn (ChatRoom $record) => $record->update(['is_active' => false, 'read_only_at' => now()])),

                Action::make('activate')
                    ->label(__('admin.actions.activate_room'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ChatRoom $record): bool => ! $record->is_active && auth()->user()?->hasAnyRole(['admin', 'super-admin']))
                    ->action(fn (ChatRoom $record) => $record->update(['is_active' => true, 'read_only_at' => null])),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.no_chat_rooms'))
            ->recordUrl(fn (ChatRoom $record): string => ChatRoomResource::getUrl('index').'?room='.$record->id);
    }
}
