<?php

namespace App\Models;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Enums\ServiceRequestStatusOutcome;
use App\Models\Concerns\HasDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use HasDeletedBy;

    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'source',
        'type_id',
        'marketing_source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'user_id',
        'name',
        'company_name',
        'phone',
        'email',
        'ip_address',
        'user_agent',
        'city',
        'message',
        'attachments',
        'course_id',
        'course_group_id',
        'bundle_id',
        'page_id',
        'status',
        'status_date',
        'team_id',
        'assigned_to',
        'priority',
        'last_activity_at',
        'converted_order_id',
        'converted_at',
        'sheet_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => ServiceRequestSource::class,
            'priority' => ServiceRequestPriority::class,
            'status_date' => 'datetime',
            'last_activity_at' => 'datetime',
            'converted_at' => 'datetime',
            'sheet_synced_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestType::class, 'type_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The landing page (version) the lead was submitted from.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function statusModel(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestStatus::class, 'status', 'slug');
    }

    /**
     * Leads still in play: the status is open (or has no outcome / no status row).
     * status_date doubles as the follow-up date, so only these can be due.
     */
    public function scopeFollowUpOpen(Builder $query): Builder
    {
        return $query
            ->whereNotNull('status_date')
            ->where(fn (Builder $q): Builder => $q
                ->whereDoesntHave('statusModel')
                ->orWhereHas('statusModel', fn (Builder $status): Builder => $status
                    ->whereNull('outcome')
                    ->orWhere('outcome', ServiceRequestStatusOutcome::Open->value)));
    }

    public function scopeFollowUpDueToday(Builder $query): Builder
    {
        return $query->followUpOpen()->whereDate('status_date', today());
    }

    public function scopeFollowUpOverdue(Builder $query): Builder
    {
        return $query->followUpOpen()->where('status_date', '<', today()->startOfDay());
    }

    public function scopeFollowUpUpcoming(Builder $query): Builder
    {
        return $query->followUpOpen()->where('status_date', '>=', today()->addDay()->startOfDay());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ServiceRequestActivity::class);
    }

    public function orderAttributions(): HasMany
    {
        return $this->hasMany(OrderAttribution::class);
    }

    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'entity');
    }

    public function crmTasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'entity');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function quotes(): HasManyThrough
    {
        return $this->hasManyThrough(Quote::class, Deal::class);
    }
}
