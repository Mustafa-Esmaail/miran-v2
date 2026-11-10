<?php

namespace App\Services;

use App\Enums\EmployeeRole;
use App\Enums\OrderStatus;
use App\Enums\SalesTargetPeriodType;
use App\Enums\ServiceRequestSource;
use App\Models\Bundle;
use App\Models\CallEvaluation;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesTarget;
use App\Models\ServiceRequest;
use App\Support\CallQuality\CallQualityLevels;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Computes every figure shown on the KPI dashboard ("لوحة المؤشرات") from the page filters.
 *
 * Sales = paid orders, filtered by order source (defaults to the sales team) and
 * scoped to the viewer's own numbers, team or department based on their employee role.
 */
class KpiDashboardService
{
    public const DEFAULT_SOURCE = 'sales';

    /** Sunday–Thursday (Carbon dayOfWeek values). */
    private const WORKING_DAYS = [0, 1, 2, 3, 4];

    /** @var Collection<int, int>|false|null false = not resolved yet, null = no restriction */
    private Collection|false|null $scopedEmployeeIds = false;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(array $filters): array
    {
        $from = filled($filters['date_from'] ?? null)
            ? CarbonImmutable::parse($filters['date_from'])->startOfDay()
            : CarbonImmutable::today()->startOfMonth();

        $to = filled($filters['date_to'] ?? null)
            ? CarbonImmutable::parse($filters['date_to'])->startOfDay()
            : $from->endOfMonth()->startOfDay();

        return $to->lessThan($from) ? [$to, $from] : [$from, $to];
    }

    /**
     * A missing key means the filters were never set, so the sales source applies; an explicit null means "all".
     *
     * @param  array<string, mixed>  $filters
     */
    public function source(array $filters): ?string
    {
        if (! array_key_exists('source', $filters)) {
            return self::DEFAULT_SOURCE;
        }

        return filled($filters['source']) ? (string) $filters['source'] : null;
    }

    public function scopeLabel(): string
    {
        $employee = $this->currentEmployee();

        return match ($employee?->role) {
            EmployeeRole::SalesPerson => $employee->name,
            EmployeeRole::TeamLead => __('admin.kpi_dashboard.scope_team'),
            EmployeeRole::DepartmentManager => __('admin.kpi_dashboard.scope_department'),
            default => __('admin.kpi_dashboard.scope_all'),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{label: ?string, target: int, achieved: int, percent: float, remaining: int, remaining_working_days: int, required_daily: int, expected_percent: int}
     */
    public function targetPace(array $filters): array
    {
        [$from, $to] = $this->range($filters);

        $months = collect(CarbonPeriod::create($from->startOfMonth(), '1 month', $to))
            ->map(fn ($month): string => $month->format('Y-m'))
            ->unique()
            ->values();

        $target = (int) SalesTarget::query()
            ->where('period_type', SalesTargetPeriodType::Monthly)
            ->whereIn('period', $months)
            ->when($this->scopedEmployeeIds(), fn (Builder $q, Collection $ids): Builder => $q->whereIn('employee_id', $ids))
            ->sum('target_revenue');

        $achieved = (int) $this->paidOrders($from, $to, $this->source($filters))->sum('total_amount');
        $remaining = max(0, $target - $achieved);

        $today = CarbonImmutable::today();
        $totalWorkingDays = $this->workingDaysBetween($from, $to);
        $remainingWorkingDays = $to->lessThan($today) ? 0 : $this->workingDaysBetween($from->max($today), $to);
        $elapsedWorkingDays = $totalWorkingDays - $remainingWorkingDays;

        return [
            'label' => $months->count() === 1 ? $from->translatedFormat('F Y') : null,
            'target' => $target,
            'achieved' => $achieved,
            'percent' => $target > 0 ? round($achieved / $target * 100, 1) : 0.0,
            'remaining' => $remaining,
            'remaining_working_days' => $remainingWorkingDays,
            'required_daily' => $remaining > 0 ? (int) ceil($remaining / max(1, $remainingWorkingDays)) : 0,
            'expected_percent' => $totalWorkingDays > 0 ? (int) round($elapsedWorkingDays / $totalWorkingDays * 100) : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{revenue: int, previous_revenue: int, previous_days: int, revenue_change: ?float, orders: int, average_order: int, last_year_revenue: int, yoy_growth: ?float, leads: int, converted_leads: int, conversion_rate: float}
     */
    public function kpis(array $filters): array
    {
        [$from, $to] = $this->range($filters);
        $source = $this->source($filters);

        $days = (int) $from->diffInDays($to) + 1;
        $previousTo = $from->subDay();
        $previousFrom = $previousTo->subDays($days - 1);

        $current = $this->paidOrders($from, $to, $source);
        $revenue = (int) (clone $current)->sum('total_amount');
        $orders = (clone $current)->count();

        $previousRevenue = (int) $this->paidOrders($previousFrom, $previousTo, $source)->sum('total_amount');
        $lastYearRevenue = (int) $this->paidOrders($from->subYearNoOverflow(), $to->subYearNoOverflow(), $source)->sum('total_amount');

        $leadsQuery = ServiceRequest::query()
            ->whereNotIn('source', [ServiceRequestSource::Complaint, ServiceRequestSource::CorporateTraining])
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->when($this->scopedUserIds(), fn (Builder $q, Collection $ids): Builder => $q->whereIn('assigned_to', $ids));

        $leads = (clone $leadsQuery)->count();
        $convertedLeads = (clone $leadsQuery)->whereNotNull('converted_at')->count();

        return [
            'revenue' => $revenue,
            'previous_revenue' => $previousRevenue,
            'previous_days' => $days,
            'revenue_change' => $this->percentChange($revenue, $previousRevenue),
            'orders' => $orders,
            'average_order' => $orders > 0 ? (int) round($revenue / $orders) : 0,
            'last_year_revenue' => $lastYearRevenue,
            'yoy_growth' => $this->percentChange($revenue, $lastYearRevenue),
            'leads' => $leads,
            'converted_leads' => $convertedLeads,
            'conversion_rate' => $leads > 0 ? round($convertedLeads / $leads * 100, 1) : 0.0,
        ];
    }

    /**
     * Average call-quality score of the evaluations (by call date) in the range that
     * the viewer may see; null when the viewer may not see call evaluations at all.
     *
     * @param  array<string, mixed>  $filters
     * @return array{average: ?float, count: int, level: ?string}|null
     */
    public function callQuality(array $filters): ?array
    {
        $user = auth('admin')->user();

        if (! ($user?->can('call_evaluations.viewAny') ?? false)) {
            return null;
        }

        [$from, $to] = $this->range($filters);
        $quality = app(CallQualityService::class);

        $average = $quality->averageScore($from, $to, $user);

        $count = $quality->scopeEvaluations(CallEvaluation::query(), $user)
            ->whereBetween('call_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('score_percent')
            ->count();

        return [
            'average' => $average,
            'count' => $count,
            'level' => CallQualityLevels::levelFor($average),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function dailySales(array $filters): array
    {
        [$from, $to] = $this->range($filters);

        $totals = $this->paidOrders($from, $to, $this->source($filters))
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(CarbonPeriod::create($from, $to));

        return [
            'labels' => $days->map(fn ($day): string => $day->format('m-d'))->all(),
            'data' => $days->map(fn ($day): int => (int) ($totals[$day->toDateString()] ?? 0))->all(),
        ];
    }

    /**
     * Revenue per primary payment method, largest first.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int> label => revenue
     */
    public function paymentMethods(array $filters): array
    {
        [$from, $to] = $this->range($filters);

        return $this->paidOrders($from, $to, $this->source($filters))
            ->with(['payments:id,order_id,provider', 'bankTransferReceipt'])
            ->get(['id', 'total_amount'])
            ->groupBy(fn (Order $order): string => $order->primary_payment_method ?? 'unknown')
            ->map(fn (Collection $orders): int => (int) $orders->sum('total_amount'))
            ->sortDesc()
            ->mapWithKeys(fn (int $total, string $method): array => [$this->paymentMethodLabel($method) => $total])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{name: string, total: int, orders_count: int}>
     */
    public function topSellers(array $filters, int $limit = 10): Collection
    {
        [$from, $to] = $this->range($filters);

        return $this->paidOrders($from, $to, $this->source($filters))
            ->whereNotNull('referred_by_employee_id')
            ->selectRaw('referred_by_employee_id, SUM(total_amount) as total, COUNT(*) as orders_count')
            ->groupBy('referred_by_employee_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('referredByEmployee:id,name')
            ->get()
            ->map(fn (Order $row): array => [
                'name' => $row->referredByEmployee?->name ?? '—',
                'total' => (int) $row->total,
                'orders_count' => (int) $row->orders_count,
            ]);
    }

    /**
     * Course groups roll up into their course; bundles are listed on their own.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{title: string, orders_count: int, total: int}>
     */
    public function topCourses(array $filters, int $limit = 8): Collection
    {
        [$from, $to] = $this->range($filters);
        $source = $this->source($filters);

        $rows = OrderItem::query()
            ->whereHas('order', fn (Builder $q): Builder => $this->applyPaidScope($q, $from, $to, $source))
            ->selectRaw('purchasable_type, purchasable_id, SUM(price_amount * quantity) as total, COUNT(DISTINCT order_id) as orders_count')
            ->groupBy('purchasable_type', 'purchasable_id')
            ->get();

        $groups = CourseGroup::query()
            ->with('course')
            ->whereIn('id', $rows->where('purchasable_type', CourseGroup::class)->pluck('purchasable_id'))
            ->get()
            ->keyBy('id');

        $bundles = Bundle::query()
            ->whereIn('id', $rows->where('purchasable_type', Bundle::class)->pluck('purchasable_id'))
            ->get()
            ->keyBy('id');

        $locale = app()->getLocale();

        return $rows
            ->map(function (OrderItem $row) use ($groups, $bundles, $locale): array {
                if ($row->purchasable_type === CourseGroup::class) {
                    $course = $groups->get($row->purchasable_id)?->course;
                    $key = 'course:'.($course?->id ?? 'g'.$row->purchasable_id);
                    $title = $course?->getTranslation('title', $locale) ?? '—';
                } else {
                    $bundle = $bundles->get($row->purchasable_id);
                    $key = 'bundle:'.$row->purchasable_id;
                    $title = $bundle?->getTranslation('title', $locale) ?? '—';
                }

                return ['key' => $key, 'title' => $title, 'total' => (int) $row->total, 'orders_count' => (int) $row->orders_count];
            })
            ->groupBy('key')
            ->map(fn (Collection $items): array => [
                'title' => $items->first()['title'],
                'orders_count' => (int) $items->sum('orders_count'),
                'total' => (int) $items->sum('total'),
            ])
            ->sortByDesc('total')
            ->take($limit)
            ->values();
    }

    /**
     * The 12 months ending with the selected period's last month, against the same months a year earlier.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, current: array<int, int>, previous: array<int, int>}
     */
    public function monthlyTrend(array $filters): array
    {
        [, $to] = $this->range($filters);
        $source = $this->source($filters);

        $end = $to->endOfMonth()->startOfDay();
        $start = $end->startOfMonth()->subMonthsNoOverflow(11);

        $current = $this->monthlyTotals($start, $end, $source);
        $previous = $this->monthlyTotals($start->subYearNoOverflow(), $end->subYearNoOverflow()->endOfMonth()->startOfDay(), $source);

        $months = collect(CarbonPeriod::create($start, '1 month', $end));

        return [
            'labels' => $months->map(fn ($month): string => $month->translatedFormat('F Y'))->all(),
            'current' => $months->map(fn ($month): int => (int) ($current[$month->format('Y-m')] ?? 0))->all(),
            'previous' => $months->map(fn ($month): int => (int) ($previous[$month->subYearNoOverflow()->format('Y-m')] ?? 0))->all(),
        ];
    }

    public function workingDaysBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        if ($to->lessThan($from)) {
            return 0;
        }

        return collect(CarbonPeriod::create($from, $to))
            ->filter(fn ($day): bool => in_array($day->dayOfWeek, self::WORKING_DAYS, true))
            ->count();
    }

    /**
     * @return Builder<Order>
     */
    private function paidOrders(CarbonImmutable $from, CarbonImmutable $to, ?string $source): Builder
    {
        return $this->applyPaidScope(Order::query(), $from, $to, $source);
    }

    private function applyPaidScope(Builder $query, CarbonImmutable $from, CarbonImmutable $to, ?string $source): Builder
    {
        return $query
            ->filteredForReport($from->toDateString(), $to->toDateString(), null, $source)
            ->where('status', OrderStatus::Paid)
            ->when($this->scopedEmployeeIds(), fn (Builder $q, Collection $ids): Builder => $q->whereIn('referred_by_employee_id', $ids));
    }

    /**
     * @return Collection<string, int> Y-m => revenue
     */
    private function monthlyTotals(CarbonImmutable $from, CarbonImmutable $to, ?string $source): Collection
    {
        return $this->paidOrders($from, $to, $source)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total): int => (int) $total);
    }

    private function percentChange(int $current, int $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    private function paymentMethodLabel(string $method): string
    {
        $key = 'admin.kpi_dashboard.payment_methods.'.$method;

        return trans()->has($key) ? __($key) : ucfirst(str_replace('_', ' ', $method));
    }

    private function currentEmployee(): ?Employee
    {
        $userId = auth('admin')->id();

        return $userId ? Employee::query()->where('user_id', $userId)->first() : null;
    }

    /**
     * @return Collection<int, int>|null null when the viewer sees the whole center
     */
    private function scopedEmployeeIds(): ?Collection
    {
        if ($this->scopedEmployeeIds !== false) {
            return $this->scopedEmployeeIds;
        }

        $employee = $this->currentEmployee();

        return $this->scopedEmployeeIds = match ($employee?->role) {
            EmployeeRole::SalesPerson => collect([$employee->id]),
            EmployeeRole::TeamLead => Employee::query()->where('team_id', $employee->team_id)->pluck('id'),
            EmployeeRole::DepartmentManager => Employee::query()->where('department_id', $employee->department_id)->pluck('id'),
            default => null,
        };
    }

    /**
     * @return Collection<int, int>|null
     */
    private function scopedUserIds(): ?Collection
    {
        $employeeIds = $this->scopedEmployeeIds();

        return $employeeIds === null
            ? null
            : Employee::query()->whereIn('id', $employeeIds)->whereNotNull('user_id')->pluck('user_id');
    }
}
