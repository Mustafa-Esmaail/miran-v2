<?php

namespace App\Models;

use App\Enums\EmployeeRole;
use App\Observers\EmployeeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(EmployeeObserver::class)]
class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'user_id',
        'department_id',
        'team_id',
        'name',
        'email',
        'phone',
        'job_title',
        'role',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role' => EmployeeRole::class,
            'commission_rate' => 'decimal:2',
            'is_active' => 'bool',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(self::class, 'team_id', 'team_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'account_manager_id');
    }

    public function referredOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'referred_by_employee_id');
    }

    public function assignedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_to', 'user_id');
    }

    public function crmActivities(): HasMany
    {
        return $this->hasMany(CrmActivity::class);
    }

    public function crmTasks(): HasMany
    {
        return $this->hasMany(CrmTask::class);
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }
}
