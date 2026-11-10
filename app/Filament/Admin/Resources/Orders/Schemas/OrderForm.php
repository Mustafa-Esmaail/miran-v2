<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\User;
use App\Support\Purchasables\PurchasableResolver;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            // ── CREATE fields ────────────────────────────────────────────
            Section::make(__('admin.sections.order_details'))
                ->columnSpanFull()
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label(__('admin.labels.customer'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('purchasable_type')
                        ->label(__('admin.labels.item_type'))
                        ->options([
                            CourseGroup::class => __('admin.options.course_group'),
                            Bundle::class => __('admin.options.bundle'),
                        ])
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('purchasable_id', null))
                        ->required(),
                    Select::make('purchasable_id')
                        ->label(__('admin.labels.item'))
                        ->options(function (Get $get) {
                            $type = $get('purchasable_type');
                            if ($type === CourseGroup::class) {
                                return CourseGroup::query()
                                    ->with('course')
                                    ->get()
                                    ->mapWithKeys(function (CourseGroup $group) {
                                        $courseTitle = $group->course?->getTranslation('title', app()->getLocale()) ?? 'Course';
                                        $groupTitle = $group->getTranslation('title', app()->getLocale());

                                        return [$group->id => $courseTitle.' - '.$groupTitle];
                                    });
                            }

                            if ($type === Bundle::class) {
                                return Bundle::query()
                                    ->get()
                                    ->mapWithKeys(fn (Bundle $bundle) => [
                                        $bundle->id => $bundle->getTranslation('title', app()->getLocale()) ?? '',
                                    ]);
                            }

                            return [];
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                            if (! $state) {
                                return;
                            }

                            $type = $get('purchasable_type');
                            $resolver = app(PurchasableResolver::class);

                            if ($type === CourseGroup::class) {
                                $group = CourseGroup::query()->with('course.discounts')->find($state);
                                if ($group) {
                                    $set('price_amount', $resolver->getPriceAmount($group));
                                }
                            }

                            if ($type === Bundle::class) {
                                $bundle = Bundle::query()->with(['discounts', 'courses'])->find($state);
                                if ($bundle) {
                                    $set('price_amount', $resolver->getPriceAmount($bundle));
                                }
                            }
                        }),
                    TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    TextInput::make('price_amount')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('SAR')
                        ->helperText(__('admin.helpers.auto_filled_from_group_price'))
                        ->required(),
                ])
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->columns(2),

            // ── VIEW / EDIT shared info ──────────────────────────────────
            Section::make(__('admin.sections.order_details'))
                ->schema([
                    TextInput::make('order_no')
                        ->label(__('admin.labels.order_no'))
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->name.' - '.$record->email, ' -'))
                        ->label(__('admin.labels.customer'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('admin.helpers.order_client_change_updates_enrollments'))
                        ->visible(fn (string $operation): bool => $operation === 'edit'),
                    Placeholder::make('customer_info')
                        ->label(__('admin.labels.customer'))
                        ->content(fn (?Order $record): string => $record?->user
                            ? $record->user->name.' — '.$record->user->email
                            : '—')
                        ->visible(fn (string $operation): bool => $operation === 'view'),
                    Placeholder::make('customer_phone_display')
                        ->label(__('admin.labels.phone'))
                        ->content(fn (?Order $record): string => $record?->user?->phone ?? '—'),
                    Placeholder::make('checkout_name_display')
                        ->label(__('admin.labels.checkout_name'))
                        ->content(fn (?Order $record): string => $record?->customer_name ?? '—'),
                    Placeholder::make('checkout_phone_display')
                        ->label(__('admin.labels.checkout_phone'))
                        ->content(fn (?Order $record): string => $record?->customer_phone ?? '—'),
                    Placeholder::make('subtotal_amount_display')
                        ->label(__('admin.labels.subtotal'))
                        ->content(fn (?Order $record): string => $record
                            ? number_format((float) $record->subtotal_amount, 2).' '.($record->currency ?? 'SAR')
                            : '—'),
                    Placeholder::make('discount_amount_display')
                        ->label(__('admin.labels.discount'))
                        ->content(fn (?Order $record): string => $record
                            ? '−'.number_format((float) $record->discount_amount, 2).' '.($record->currency ?? 'SAR')
                            : '—'),
                    Placeholder::make('total_amount_display')
                        ->label(__('admin.labels.total'))
                        ->content(fn (?Order $record): string => $record
                            ? number_format((float) $record->total_amount, 2).' '.($record->currency ?? 'SAR')
                            : '—'),
                    Placeholder::make('currency_display')
                        ->label(__('admin.labels.currency'))
                        ->content(fn (?Order $record): string => $record?->currency ?? '—'),
                ])
                ->visible(fn (string $operation): bool => in_array($operation, ['view', 'edit']))
                ->columns(2),

            // ── EDIT-only: coupon + status + notes ───────────────────────
            Section::make(__('admin.sections.status'))
                ->schema([
                    Select::make('coupon_id')
                        ->relationship('coupon', 'code')
                        ->label(__('admin.labels.coupon'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->nullable(),
                    Select::make('source')
                        ->label(__('admin.labels.order_source'))
                        ->options([
                            'site' => __('admin.options.order_source_site'),
                            'store' => __('admin.options.order_source_store'),
                            'sales' => __('admin.options.order_source_sales'),
                            'admin' => __('admin.options.order_source_admin'),
                        ])
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state !== 'sales') {
                                $set('referred_by_employee_id', null);
                            }
                        })
                        ->required(),
                    Select::make('referred_by_employee_id')
                        ->relationship('referredByEmployee', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
                        ->label(__('admin.labels.referred_by'))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn (Get $get): bool => $get('source') === 'sales'),
                    Select::make('status')
                        ->label(__('admin.labels.status'))
                        ->options([
                            'pending' => __('admin.options.pending'),
                            'paid' => __('admin.options.paid'),
                            'rejected' => __('admin.options.rejected'),
                            'refunded' => __('admin.options.refunded'),
                            'cancelled' => __('admin.options.cancelled'),
                        ]),
                    Textarea::make('notes')
                        ->required(function (Get $get, ?Order $record): bool {
                            if (! $record) {
                                return false;
                            }

                            return (string) ($get('coupon_id') ?? '') !== (string) ($record->coupon_id ?? '');
                        })
                        ->helperText(__('admin.helpers.order_price_edit_notes_required'))
                        ->columnSpanFull(),
                ])
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->columns(2),

            // ── VIEW-only: coupon + status + notes ───────────────────────
            Section::make(__('admin.sections.status'))
                ->schema([
                    Placeholder::make('coupon_info')
                        ->label(__('admin.labels.coupon'))
                        ->content(function (?Order $record): string {
                            if (! $record?->coupon) {
                                return '—';
                            }
                            $discount = number_format((float) $record->discount_amount, 2);
                            $currency = $record->currency ?? 'SAR';

                            return $record->coupon->code.'  (−'.$discount.' '.$currency.')';
                        }),
                    Placeholder::make('status_display')
                        ->label(__('admin.labels.status'))
                        ->content(fn (?Order $record): string => $record?->status instanceof \BackedEnum
                            ? __('admin.options.'.$record->status->value)
                            : '—'),
                    Placeholder::make('source_display')
                        ->label(__('admin.labels.order_source'))
                        ->content(function (?Order $record): string {
                            if (! $record) {
                                return '—';
                            }

                            $value = $record->source instanceof \BackedEnum ? $record->source->value : $record->source;
                            $key = 'admin.options.order_source_'.$value;
                            $label = __($key) !== $key ? __($key) : (string) $value;

                            if ($value === 'sales' && $record->referredByEmployee) {
                                $label .= ' — '.$record->referredByEmployee->name;
                            }

                            if ($record->marketing_source) {
                                $label .= ' — '.$record->marketing_source;
                            }

                            return $label;
                        }),
                    Placeholder::make('notes_display')
                        ->label(__('admin.labels.notes'))
                        ->content(fn (?Order $record): string => $record?->notes ?? '—')
                        ->columnSpanFull(),
                    Placeholder::make('referred_by_display')
                        ->label(__('admin.labels.referred_by'))
                        ->content(fn (?Order $record): string => $record?->referredByEmployee?->name ?? '—'),
                    Placeholder::make('sheet_synced_at_display')
                        ->label(__('admin.labels.sheet_synced_at'))
                        ->content(fn (?Order $record): string => $record?->sheet_synced_at
                            ? __('admin.options.synced').' ('.$record->sheet_synced_at->format('Y-m-d H:i').')'
                            : __('admin.options.not_synced')),
                    Placeholder::make('service_request_display')
                        ->label(__('admin.labels.service_request'))
                        ->content(function (?Order $record): string|\Illuminate\Contracts\Support\Htmlable {
                            $serviceRequest = $record ? \App\Models\ServiceRequest::query()->where('converted_order_id', $record->id)->first() : null;
                            if (! $serviceRequest) {
                                return '—';
                            }

                            $url = \App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource::getUrl('edit', ['record' => $serviceRequest->id]);

                            return new \Illuminate\Support\HtmlString('<a href="'.$url.'" class="text-primary-600 underline">#'.$serviceRequest->id.'</a>');
                        }),
                ])
                ->visible(fn (string $operation): bool => $operation === 'view')
                ->columns(2),

            // ── VIEW-only: soft-delete info (only admins can reach a trashed record) ──
            Section::make(__('admin.sections.deletion_details'))
                ->schema([
                    Placeholder::make('deleted_by_display')
                        ->label(__('admin.labels.deleted_by'))
                        ->content(fn (?Order $record): string => $record?->deletedBy?->name ?? '—'),
                    Placeholder::make('deleted_at_display')
                        ->label(__('admin.labels.deleted_at'))
                        ->content(fn (?Order $record): string => $record?->deleted_at?->format('Y-m-d H:i') ?? '—'),
                ])
                ->visible(fn (string $operation, ?Order $record): bool => $operation === 'view' && $record?->deleted_at !== null)
                ->columns(2),
        ])->columns(2);
    }
}
