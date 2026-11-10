<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasOrderReportFilters;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\OrderItem;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderLineItemsReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasOrderReportFilters, InteractsWithTable {
        HasOrderReportFilters::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.order-line-items-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.order_line_items_report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.commerce');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.reports');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('order_line_items_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.order_line_items_report');
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Today;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('payment_method')
                    ->label(__('admin.labels.payment_method'))
                    ->options([
                        'bank_transfer' => __('admin.labels.bank_transfer_provider'),
                        'tamara' => 'Tamara',
                        'cod' => __('admin.labels.cod'),
                    ])
                    ->placeholder(__('admin.labels.all')),
                Select::make('source')
                    ->label(__('admin.labels.order_source'))
                    ->visible($this->getLockedSource() === null)
                    ->options([
                        'site' => __('admin.options.order_source_site'),
                        'store' => __('admin.options.order_source_store'),
                        'sales' => __('admin.options.order_source_sales'),
                    ])
                    ->placeholder(__('admin.labels.all'))
                    ->default('sales'),
                Select::make('status')
                    ->label(__('admin.labels.status'))
                    ->options([
                        'pending' => __('admin.options.pending'),
                        'paid' => __('admin.options.paid'),
                        'rejected' => __('admin.options.rejected'),
                        'refunded' => __('admin.options.refunded'),
                        'cancelled' => __('admin.options.cancelled'),
                    ])
                    ->placeholder(__('admin.labels.all'))
                    ->default('paid'),
            ]);
    }

    protected function baseQuery(): Builder
    {
        return OrderItem::query()
            ->select([
                'order_items.*',
                'course_groups.id as joined_course_group_id',
                'course_groups.title as joined_course_group_title',
                'courses.title as joined_course_title',
            ])
            ->withCourseGroupRows()
            ->leftJoin('courses', 'course_groups.course_id', '=', 'courses.id')
            ->selectSub(
                OrderItem::query()->from('order_items as sibling_items')->selectRaw('count(*)')
                    ->whereColumn('sibling_items.order_id', 'order_items.order_id'),
                'sibling_items_count'
            )
            ->selectSub(
                OrderItem::query()->from('order_items as purchasable_sales')->selectRaw('count(*)')
                    ->whereColumn('purchasable_sales.purchasable_type', 'order_items.purchasable_type')
                    ->whereColumn('purchasable_sales.purchasable_id', 'order_items.purchasable_id'),
                'purchasable_sales_count'
            )
            ->whereHas('order', fn (Builder $o): Builder => $o->filteredForReport(
                $this->reportFilterValue('date_from'),
                $this->reportFilterValue('date_to'),
                $this->reportFilterValue('payment_method'),
                $this->reportSourceFilterValue(),
                $this->reportFilterValue('status'),
            ))
            ->with(['order.coupon', 'order.payments', 'order.bankTransferReceipt', 'order.referredByEmployee', 'purchasable']);
    }

    private function translateJson(?string $json): ?string
    {
        if (! $json) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return $json;
        }

        $locale = app()->getLocale();

        return $decoded[$locale] ?? $decoded['en'] ?? array_values($decoded)[0] ?? null;
    }

    public function getTableRecordKey($record): string
    {
        return $record->id.'_'.($record->joined_course_group_id ?? 0);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->baseQuery())
            ->recordUrl(fn (OrderItem $record): string => OrderResource::getUrl('view', ['record' => $record->order_id]))
            ->columns($this->lineItemColumns())
            ->groups([
                Group::make('order_id')
                    ->label(__('admin.labels.order_no'))
                    ->getTitleFromRecordUsing(fn (OrderItem $record): string => $record->order->order_no),
            ])
            ->defaultGroup('order_id')
            ->defaultSort('purchasable_sales_count', 'desc');
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function lineItemColumns(): array
    {
        return [
            TextColumn::make('order.order_no')->label(__('admin.labels.order_no'))->searchable(),
            TextColumn::make('title_snapshot')
                ->label(__('admin.labels.course'))
                ->state(fn (OrderItem $record): string => $this->lineItemTitle($record))
                ->searchable(),
            TextColumn::make('course_group_title')
                ->label(__('admin.labels.course_group'))
                ->state(fn (OrderItem $record): ?string => $this->lineItemCourseGroupTitle($record))
                ->url(fn (OrderItem $record): ?string => ($record->joined_course_group_id ?? ($record->purchasable_type === CourseGroup::class ? $record->purchasable_id : null))
                    ? CourseGroupResource::getUrl('edit', ['record' => $record->joined_course_group_id ?? $record->purchasable_id])
                    : null)
                ->color('primary')
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('type')
                ->label(__('admin.labels.type'))
                ->state(fn (OrderItem $record): string => $this->lineItemType($record))
                ->formatStateUsing(fn (string $state): string => __('admin.options.order_item_type_'.$state))
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'bundle' => 'warning',
                    'multi_item_cart' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('order.coupon.code')->label(__('admin.labels.coupon'))->placeholder('—'),
            TextColumn::make('order.source')
                ->label(__('admin.labels.order_source'))
                ->formatStateUsing(fn ($state, OrderItem $record): string => $this->lineItemSourceLabel($record))
                ->badge()
                ->color(function ($state): string {
                    $value = is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state;

                    return match ($value) {
                        'sales' => 'info',
                        'admin' => 'warning',
                        'store' => 'gray',
                        default => 'secondary',
                    };
                }),
            TextColumn::make('order.primary_payment_method')
                ->label(__('admin.labels.payment_method'))
                ->formatStateUsing(fn (?string $state): string => $this->paymentMethodLabel($state)),
            TextColumn::make('order.status')
                ->label(__('admin.labels.status'))
                ->formatStateUsing(fn ($state): string => $this->orderStatusLabel($state))
                ->badge()
                ->color(function ($state): string {
                    $value = is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state;

                    return match ($value) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'refunded' => 'gray',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    };
                }),
            TextColumn::make('price_amount')
                ->label(__('admin.labels.total'))
                ->state(fn (OrderItem $record): float => $this->lineItemAmount($record))
                ->formatStateUsing(fn ($state, OrderItem $record) => number_format((float) $state, 2).' '.($record->order->currency ?? '')),
            TextColumn::make('order.created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ];
    }

    protected function exportName(): string
    {
        return 'order-line-items-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.order_no'),
            __('admin.labels.course'),
            __('admin.labels.course_group'),
            __('admin.labels.type'),
            __('admin.labels.coupon'),
            __('admin.labels.order_source'),
            __('admin.labels.payment_method'),
            __('admin.labels.status'),
            __('admin.labels.total'),
            __('admin.labels.created_at'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        return $this->getFilteredSortedTableQuery()
            ->get()
            ->map(fn (OrderItem $record): array => [
                $record->order?->order_no,
                $this->lineItemTitle($record),
                $this->lineItemCourseGroupTitle($record),
                __('admin.options.order_item_type_'.$this->lineItemType($record)),
                $record->order?->coupon?->code,
                $this->lineItemSourceLabel($record),
                $this->paymentMethodLabel($record->order?->primary_payment_method),
                $this->orderStatusLabel($record->order?->status),
                round($this->lineItemAmount($record), 2),
                $record->order?->created_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    private function lineItemTitle(OrderItem $record): string
    {
        if ($record->purchasable_type === Bundle::class && $record->joined_course_title) {
            return $record->title_snapshot.' — '.$this->translateJson($record->joined_course_title);
        }

        return $record->title_snapshot;
    }

    private function lineItemCourseGroupTitle(OrderItem $record): ?string
    {
        if ($record->purchasable_type === CourseGroup::class) {
            return $record->purchasable?->title;
        }

        if ($record->purchasable_type !== Bundle::class) {
            return null;
        }

        if ($record->joined_course_group_title) {
            return $this->translateJson($record->joined_course_group_title);
        }

        $groupIds = collect(data_get($record->metadata_snapshot, 'selected_course_group_ids', []))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique();

        if ($groupIds->isEmpty()) {
            return null;
        }

        return CourseGroup::query()
            ->whereIn('id', $groupIds)
            ->get()
            ->map(fn (CourseGroup $group): string => $group->title)
            ->implode(', ');
    }

    private function lineItemType(OrderItem $record): string
    {
        if ($record->purchasable_type === Bundle::class) {
            return 'bundle';
        }

        return ((int) $record->sibling_items_count) > 1 ? 'multi_item_cart' : 'single_item';
    }

    private function lineItemSourceLabel(OrderItem $record): string
    {
        $state = $record->order?->source;
        $value = $state instanceof \BackedEnum ? $state->value : $state;
        $key = 'admin.options.order_source_'.$value;
        $label = __($key) !== $key ? __($key) : (string) $value;

        if ($value === 'sales' && $record->order->referredByEmployee) {
            $label .= ' — '.$record->order->referredByEmployee->name;
        }

        return $label;
    }

    private function paymentMethodLabel(?string $method): string
    {
        return match (strtolower((string) $method)) {
            'bank_transfer', 'bank' => __('admin.labels.bank_transfer_provider'),
            'tamara' => 'Tamara',
            'cod', 'cash_on_delivery' => __('admin.labels.cod'),
            '' => '—',
            default => ucfirst((string) $method),
        };
    }

    private function orderStatusLabel(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? $state->value : $state;
        $key = 'admin.options.'.$value;

        return __($key) !== $key ? __($key) : (string) $value;
    }

    private function lineItemAmount(OrderItem $record): float
    {
        if ($record->purchasable_type === Bundle::class) {
            $selectedCount = count(data_get($record->metadata_snapshot, 'selected_course_group_ids', []));

            if ($selectedCount > 0) {
                return (float) $record->price_amount / $selectedCount;
            }
        }

        return (float) $record->price_amount;
    }
}
