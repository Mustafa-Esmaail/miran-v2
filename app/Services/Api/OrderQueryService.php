<?php

namespace App\Services\Api;

use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class OrderQueryService
{
    private const DEFAULT_PER_PAGE = 25;

    /**
     * @param  array{status?: ?string, from?: ?string, to?: ?string, updated_since?: ?string, per_page?: ?int}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $orders = $this->baseQuery()
            ->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))
            ->when($filters['updated_since'] ?? null, fn (Builder $q, string $date): Builder => $q->where('updated_at', '>=', $date))
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();

        $this->attachSelectedBundleGroups($orders->getCollection());

        return $orders;
    }

    public function findByOrderNo(string $orderNo): Order
    {
        $order = $this->baseQuery()->where('order_no', $orderNo)->firstOrFail();

        $this->attachSelectedBundleGroups(collect([$order]));

        return $order;
    }

    private function baseQuery(): Builder
    {
        return Order::query()->with([
            'user:id,name,email',
            'items.purchasable' => fn (MorphTo $morphTo): MorphTo => $morphTo->morphWith([
                CourseGroup::class => ['course'],
                Bundle::class => ['courses'],
            ]),
            'coupon' => fn ($query) => $query->withTrashed(),
            'referredByEmployee.team',
            'payments',
            'bankTransferReceipt',
        ]);
    }

    /**
     * Bundle items store the course groups the customer picked in their metadata
     * snapshot; load them all in one query and set them as "selectedCourseGroups".
     *
     * @param  Collection<int, Order>  $orders
     */
    private function attachSelectedBundleGroups(Collection $orders): void
    {
        $bundleItems = $orders
            ->flatMap(fn (Order $order): Collection => $order->items)
            ->filter(fn (OrderItem $item): bool => $item->purchasable_type === Bundle::class);

        if ($bundleItems->isEmpty()) {
            return;
        }

        $groups = CourseGroup::query()
            ->withTrashed()
            ->with('course')
            ->whereIn('id', $bundleItems->flatMap(fn (OrderItem $item): array => $this->selectedGroupIds($item))->unique())
            ->get()
            ->keyBy('id');

        foreach ($bundleItems as $item) {
            $item->setRelation('selectedCourseGroups', collect($this->selectedGroupIds($item))
                ->map(fn (int $id): ?CourseGroup => $groups->get($id))
                ->filter()
                ->values());
        }
    }

    /**
     * @return array<int, int>
     */
    private function selectedGroupIds(OrderItem $item): array
    {
        return array_map('intval', data_get($item->metadata_snapshot, 'selected_course_group_ids', []));
    }
}
