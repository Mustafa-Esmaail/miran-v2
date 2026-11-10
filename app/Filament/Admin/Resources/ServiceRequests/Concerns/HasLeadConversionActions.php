<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\ServiceRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * "Sell from lead": open a pre-filled order for this lead, or link an order that
 * already exists. Both only set converted_order_id — ServiceRequestObserver then
 * stamps converted_at, logs the conversion and runs order attribution.
 */
trait HasLeadConversionActions
{
    /**
     * @return array<int, Action>
     */
    protected function leadConversionActions(): array
    {
        return [
            Action::make('create_order_from_lead')
                ->label(__('admin.labels.create_order_from_lead'))
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->visible(fn (): bool => $this->canConvertLead() && (auth('admin')->user()?->can('orders.create') ?? false))
                ->url(fn (): string => OrderResource::getUrl('create', ['service_request' => $this->getRecord()->getKey()])),

            Action::make('link_order_to_lead')
                ->label(__('admin.labels.link_order'))
                ->icon('heroicon-o-link')
                ->color('gray')
                ->visible(fn (): bool => $this->canConvertLead())
                ->schema([
                    Select::make('order_id')
                        ->label(__('admin.labels.order'))
                        ->options(fn (): array => $this->customerOrderOptions())
                        ->getSearchResultsUsing(fn (string $search): array => Order::query()
                            ->where('order_no', 'like', '%'.$search.'%')
                            ->latest()
                            ->limit(20)
                            ->pluck('order_no', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var ServiceRequest $lead */
                    $lead = $this->getRecord();
                    $lead->update(['converted_order_id' => (int) $data['order_id']]);

                    Notification::make()->success()->title(__('filament-actions::edit.single.notifications.saved.title'))->send();
                    $this->refreshFormData(['converted_order_id', 'converted_at']);
                }),
        ];
    }

    private function canConvertLead(): bool
    {
        /** @var ServiceRequest $lead */
        $lead = $this->getRecord();

        return $lead->converted_order_id === null
            && $lead->source !== ServiceRequestSource::Complaint
            && (auth('admin')->user()?->can('service_requests.update') ?? false);
    }

    /**
     * The lead's customer's recent orders, offered before any search is typed.
     *
     * @return array<int, string>
     */
    private function customerOrderOptions(): array
    {
        /** @var ServiceRequest $lead */
        $lead = $this->getRecord();

        if (! $lead->user_id) {
            return [];
        }

        return Order::query()
            ->where('user_id', $lead->user_id)
            ->latest()
            ->limit(20)
            ->pluck('order_no', 'id')
            ->all();
    }
}
