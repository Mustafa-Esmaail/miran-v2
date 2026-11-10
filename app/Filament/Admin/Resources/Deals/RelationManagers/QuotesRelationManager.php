<?php

namespace App\Filament\Admin\Resources\Deals\RelationManagers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Services\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.quotes');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('deal_id')
                        ->label(__('admin.models.deal'))
                        ->options(fn () => $this->getOwnerRecord()->deals()->orderByDesc('id')->pluck('title', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->visible(fn (): bool => $this->getOwnerRecord() instanceof ServiceRequest)
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label(__('admin.labels.status'))
                        ->options(collect(QuoteStatus::cases())->mapWithKeys(
                            fn (QuoteStatus $s) => [$s->value => __('admin.options.'.$s->value)]
                        ))
                        ->required()
                        ->default(QuoteStatus::Draft->value),

                    DatePicker::make('valid_until')
                        ->label(__('admin.labels.valid_until')),

                    Textarea::make('notes')
                        ->label(__('admin.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('admin.sections.pricing'))
                ->schema([
                    Repeater::make('items')
                        ->label(__('admin.labels.quote_items'))
                        ->schema([
                            TextInput::make('description')
                                ->label(__('admin.labels.description'))
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('qty')
                                ->label(__('admin.labels.qty'))
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            TextInput::make('unit_price')
                                ->label(__('admin.labels.unit_price'))
                                ->numeric()
                                ->suffix(__('admin.labels.sar'))
                                ->default(0)
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set): void {
                                    $set('total', (int) $state * (int) ($get('qty') ?: 1));
                                }),
                            TextInput::make('total')
                                ->label(__('admin.labels.total'))
                                ->numeric()
                                ->suffix(__('admin.labels.sar'))
                                ->default(0),
                        ])
                        ->columns(5)
                        ->columnSpanFull()
                        ->addActionLabel(__('admin.actions.add_item'))
                        ->defaultItems(0),

                    TextInput::make('subtotal')
                        ->label(__('admin.labels.subtotal'))
                        ->numeric()
                        ->suffix(__('admin.labels.sar'))
                        ->default(0),

                    TextInput::make('discount')
                        ->label(__('admin.labels.discount'))
                        ->numeric()
                        ->suffix(__('admin.labels.sar'))
                        ->default(0),

                    TextInput::make('total')
                        ->label(__('admin.labels.total'))
                        ->numeric()
                        ->suffix(__('admin.labels.sar'))
                        ->default(0),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_no')->label(__('admin.labels.quote_no'))->searchable(),
                TextColumn::make('deal.title')
                    ->label(__('admin.models.deal'))
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof ServiceRequest),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'accepted' => 'success',
                        'sent' => 'info',
                        'draft' => 'gray',
                        'rejected' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label(__('admin.labels.total'))
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2).' '.__('admin.labels.sar')),
                TextColumn::make('valid_until')->label(__('admin.labels.valid_until'))->date('Y-m-d'),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.actions.create_quote'))
                    ->using(fn (array $data): Quote => $this->getOwnerRecord() instanceof ServiceRequest
                        ? $this->getOwnerRecord()->deals()->findOrFail($data['deal_id'])->quotes()->create($data)
                        : $this->getOwnerRecord()->quotes()->create($data)),
            ])
            ->recordActions([
                Action::make('download_pdf')
                    ->label(__('admin.actions.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (Quote $record) => app(QuoteService::class)->downloadPdf($record)),

                Action::make('mark_sent')
                    ->label(__('admin.actions.mark_as_sent'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function (Quote $record): void {
                        app(QuoteService::class)->markAsSent($record);
                        Notification::make()->title(__('admin.notifications.quote_sent'))->success()->send();
                    }),

                Action::make('mark_accepted')
                    ->label(__('admin.actions.mark_as_accepted'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Sent)
                    ->requiresConfirmation()
                    ->action(function (Quote $record): void {
                        app(QuoteService::class)->markAsAccepted($record);
                        Notification::make()->title(__('admin.notifications.quote_accepted'))->success()->send();
                    }),

                Action::make('mark_rejected')
                    ->label(__('admin.actions.mark_as_rejected'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Sent)
                    ->requiresConfirmation()
                    ->action(function (Quote $record): void {
                        app(QuoteService::class)->markAsRejected($record);
                        Notification::make()->title(__('admin.notifications.quote_rejected'))->warning()->send();
                    }),

                Action::make('mark_expired')
                    ->label(__('admin.actions.mark_as_expired'))
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (Quote $record): bool => in_array($record->status, [QuoteStatus::Draft, QuoteStatus::Sent]))
                    ->action(function (Quote $record): void {
                        app(QuoteService::class)->markAsExpired($record);
                        Notification::make()->title(__('admin.notifications.quote_expired'))->warning()->send();
                    }),

                EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quotes.heading'))
            ->emptyStateDescription(__('admin.empty_states.quotes.description'));
    }
}
