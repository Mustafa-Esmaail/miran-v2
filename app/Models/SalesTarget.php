<?php

namespace App\Models;

use App\Enums\SalesTargetPeriodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SalesTarget extends Model
{
    /** @use HasFactory<\Database\Factories\SalesTargetFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_type',
        'period',
        'target_revenue',
        'target_leads',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => SalesTargetPeriodType::class,
            'target_revenue' => 'int',
            'target_leads' => 'int',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActualRevenue(): int
    {
        $query = CommissionLedger::query()->where('employee_id', $this->employee_id);

        if ($this->period_type === SalesTargetPeriodType::Monthly) {
            $query->where('period', $this->period);
        } else {
            $query->whereIn('period', $this->getQuarterMonths());
        }

        return (int) $query->sum('gross_amount');
    }

    public function getActualLeads(): int
    {
        $userId = $this->employee?->user_id;
        if (! $userId) {
            return 0;
        }

        $query = ServiceRequest::query()
            ->where('assigned_to', $userId)
            ->whereNotNull('converted_at');

        if ($this->period_type === SalesTargetPeriodType::Monthly) {
            $query->where(DB::raw("DATE_FORMAT(converted_at, '%Y-%m')"), $this->period);
        } else {
            $months = $this->getQuarterMonths();
            $query->where(function ($q) use ($months): void {
                foreach ($months as $month) {
                    $q->orWhere(DB::raw("DATE_FORMAT(converted_at, '%Y-%m')"), $month);
                }
            });
        }

        return $query->count();
    }

    public function getRevenueHitPercent(): int
    {
        if ($this->target_revenue === 0) {
            return 0;
        }

        return (int) min(100, round($this->getActualRevenue() / $this->target_revenue * 100));
    }

    public function getLeadsHitPercent(): int
    {
        if ($this->target_leads === 0) {
            return 0;
        }

        return (int) min(100, round($this->getActualLeads() / $this->target_leads * 100));
    }

    /** @return array<string> */
    private function getQuarterMonths(): array
    {
        preg_match('/(\d{4})-Q(\d)/', $this->period, $matches);
        $year = $matches[1] ?? date('Y');
        $quarter = (int) ($matches[2] ?? 1);
        $startMonth = ($quarter - 1) * 3 + 1;

        return array_map(
            fn (int $offset): string => $year.'-'.str_pad((string) ($startMonth + $offset), 2, '0', STR_PAD_LEFT),
            [0, 1, 2]
        );
    }
}
