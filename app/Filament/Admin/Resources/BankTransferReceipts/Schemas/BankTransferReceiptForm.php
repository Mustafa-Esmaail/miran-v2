<?php

namespace App\Filament\Admin\Resources\BankTransferReceipts\Schemas;

use App\Enums\BankTransferStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankTransferReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.receipt'))
                ->columnSpanFull()
                ->schema([
                    Select::make('order_id')
                        ->relationship('order', 'order_no')
                        ->label(__('admin.labels.order'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)
                        ->label(__('admin.labels.user'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    FileUpload::make('receipt_path')
                        ->label(__('admin.labels.receipt_file'))
                        ->disk('s3')
                        ->directory('bank-receipts')
                        ->visibility('private')
                        ->required(),
                    Select::make('status')
                        ->options([
                            BankTransferStatus::Submitted->value => __('admin.options.submitted'),
                            BankTransferStatus::Approved->value => __('admin.options.approved'),
                            BankTransferStatus::Rejected->value => __('admin.options.rejected'),
                            BankTransferStatus::Replaced->value => __('admin.options.replaced'),
                        ])
                        ->required(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.review'))
                ->columnSpanFull()
                ->schema([
                    Select::make('reviewed_by')
                        ->relationship('reviewer', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)
                        ->label(__('admin.labels.reviewed_by'))
                        ->searchable()
                        ->preload(),
                    DateTimePicker::make('reviewed_at')
                        ->seconds(false)
                        ->label(__('admin.labels.reviewed_at')),
                    Textarea::make('review_notes')
                        ->label(__('admin.labels.review_notes'))
                        ->rows(4),
                ])
                ->columns(2),
        ]);
    }
}
