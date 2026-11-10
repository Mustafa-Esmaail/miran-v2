<?php

namespace App\Filament\Admin\Resources\Invoices\Schemas;

use App\Enums\InvoiceRecipientType;
use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\Invoices\Concerns\ScopesInvoicesToSalesAgent;
use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InvoiceForm
{
    use ScopesInvoicesToSalesAgent;

    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.invoice'))
                ->columnSpanFull()
                ->schema([
                    Select::make('order_id')
                        ->relationship(
                            'order',
                            'order_no',
                            modifyQueryUsing: fn (Builder $query): Builder => self::scopeOrdersForInvoiceCreation($query),
                        )
                        ->label(__('admin.labels.order'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->disabled(fn (string $operation): bool => $operation !== 'create')
                        ->dehydrated()
                        ->afterStateUpdated(function (Set $set, ?int $state): void {
                            $order = $state ? Order::query()->find($state) : null;

                            $set('recipient_name', $order?->customer_name);
                        }),
                    TextInput::make('invoice_number')
                        ->label(__('admin.labels.invoice_number'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(60)
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                    DateTimePicker::make('issued_at')
                        ->seconds(false)
                        ->label(__('admin.labels.issued_at'))
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                    Select::make('status')
                        ->label(__('admin.labels.status'))
                        ->options([
                            InvoiceStatus::Draft->value => __('admin.options.draft'),
                            InvoiceStatus::Pending->value => __('admin.options.pending'),
                            InvoiceStatus::Rejected->value => __('admin.options.rejected'),
                            InvoiceStatus::Submitted->value => __('admin.options.submitted'),
                        ])
                        ->required()
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                    FileUpload::make('invoice_path')
                        ->label(__('admin.labels.invoice_file'))
                        ->disk('s3')
                        ->directory('invoices')
                        ->visibility('private')
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                ])
                ->columns(2),
            Section::make(__('admin.sections.order_details'))
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => filled($get('order_id')))
                ->schema([
                    Placeholder::make('order_preview_customer_name')
                        ->label(__('admin.labels.checkout_name'))
                        ->content(fn (Get $get): string => self::previewOrder($get)?->customer_name ?? '—'),
                    Placeholder::make('order_preview_customer_phone')
                        ->label(__('admin.labels.checkout_phone'))
                        ->content(fn (Get $get): string => self::previewOrder($get)?->customer_phone ?? '—'),
                    Placeholder::make('order_preview_coupon_code')
                        ->label(__('admin.labels.coupon'))
                        ->content(fn (Get $get): string => self::previewOrder($get)?->coupon?->code ?? '—'),
                    Placeholder::make('order_preview_subtotal_amount')
                        ->label(__('admin.labels.subtotal'))
                        ->content(fn (Get $get): string => (string) (self::previewOrder($get)?->subtotal_amount ?? '—')),
                    Placeholder::make('order_preview_discount_amount')
                        ->label(__('admin.labels.discount'))
                        ->content(fn (Get $get): string => (string) (self::previewOrder($get)?->discount_amount ?? '—')),
                    Placeholder::make('order_preview_total_amount')
                        ->label(__('admin.labels.total'))
                        ->content(fn (Get $get): string => (string) (self::previewOrder($get)?->total_amount ?? '—')),
                ])
                ->columns(3),
            Section::make(__('admin.sections.recipient'))
                ->columnSpanFull()
                ->schema([
                    Select::make('recipient_type')
                        ->label(__('admin.labels.recipient_type'))
                        ->options([
                            InvoiceRecipientType::Person->value => __('admin.options.person'),
                            InvoiceRecipientType::Company->value => __('admin.options.company'),
                        ])
                        ->default(InvoiceRecipientType::Person->value)
                        ->required()
                        ->live(),
                    TextInput::make('recipient_name')
                        ->label(__('admin.labels.recipient_name'))
                        ->maxLength(191)
                        ->visible(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Person->value)
                        ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Person->value),
                ])
                ->columns(2),
            Section::make(__('admin.sections.company_details'))
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value)
                ->schema([
                    TextInput::make('company_legal_name')
                        ->label(__('admin.labels.company_legal_name'))
                        ->maxLength(191)
                        ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value)
                        ->columnSpanFull(),
                    FileUpload::make('company_tax_certificate_path')
                        ->label(__('admin.labels.company_tax_certificate'))
                        ->disk('s3')
                        ->directory('invoices/certificates')
                        ->visibility('private')
                        ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                    FileUpload::make('company_cr_certificate_path')
                        ->label(__('admin.labels.company_cr_certificate'))
                        ->disk('s3')
                        ->directory('invoices/certificates')
                        ->visibility('private')
                        ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                    TextInput::make('company_phone')
                        ->label(__('admin.labels.company_phone'))
                        ->tel()
                        ->maxLength(60)
                        ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                    TextInput::make('company_website')
                        ->label(__('admin.labels.company_website'))
                        ->url()
                        ->maxLength(191),
                    Section::make(__('admin.sections.national_address'))
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('company_city')
                                ->label(__('admin.labels.company_city'))
                                ->maxLength(120)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                            TextInput::make('company_district')
                                ->label(__('admin.labels.company_district'))
                                ->maxLength(120)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                            TextInput::make('company_street')
                                ->label(__('admin.labels.company_street'))
                                ->maxLength(120)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                            TextInput::make('company_building_number')
                                ->label(__('admin.labels.company_building_number'))
                                ->maxLength(30)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                            TextInput::make('company_postal_code')
                                ->label(__('admin.labels.company_postal_code'))
                                ->maxLength(30)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                            TextInput::make('company_additional_number')
                                ->label(__('admin.labels.company_additional_number'))
                                ->maxLength(30)
                                ->required(fn (Get $get): bool => $get('recipient_type') === InvoiceRecipientType::Company->value),
                        ])
                        ->columns(3),
                ])
                ->columns(2),
        ]);
    }

    private static function previewOrder(Get $get): ?Order
    {
        $orderId = $get('order_id');

        return $orderId ? Order::query()->with('coupon')->find($orderId) : null;
    }
}
