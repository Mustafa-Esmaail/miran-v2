<?php

namespace App\Filament\Admin\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.payment'))
                ->columnSpanFull()
                ->schema([
                    Select::make('order_id')
                        ->relationship('order', 'order_no')
                        ->label(__('admin.labels.order'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('provider')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('provider_reference')
                        ->label(__('admin.labels.provider_reference'))
                        ->maxLength(255),
                    Select::make('status')
                        ->options([
                            PaymentStatus::Pending->value => __('admin.options.pending'),
                            PaymentStatus::Authorized->value => __('admin.options.authorized'),
                            PaymentStatus::Paid->value => __('admin.options.paid'),
                            PaymentStatus::Failed->value => __('admin.options.failed'),
                            PaymentStatus::Refunded->value => __('admin.options.refunded'),
                        ])
                        ->required(),
                    TextInput::make('amount')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Textarea::make('payload')
                        ->rows(6),
                ])
                ->columns(2),
        ]);
    }
}
