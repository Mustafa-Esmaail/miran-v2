<?php

namespace App\Models;

use App\Observers\ShareLinkObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ShareLinkObserver::class)]
class ShareLink extends Model
{
    protected $fillable = [
        'employee_id',
        'created_by_id',
        'target_type',
        'target_id',
        'target_label',
        'full_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            if (empty($link->created_by_id)) {
                $link->created_by_id = auth('admin')->id() ?? auth()->id();
            }

            if (empty($link->full_url)) {
                $link->full_url = $link->computeUrl();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function computeUrl(): string
    {
        $employee = $this->employee ?? Employee::find($this->employee_id);
        $code = $employee?->employee_code ?? ('EMP-'.$this->employee_id);
        $ref = '?source='.rawurlencode($code);

        return match ($this->target_type) {
            'course' => $this->target_id
                ? (route('courses.show', Course::find($this->target_id)?->slug ?? '').$ref)
                : (url('/').$ref),
            'bundle' => $this->target_id
                ? (url('/bundles/'.(Bundle::find($this->target_id)?->slug ?? '')).$ref)
                : (url('/').$ref),
            'category' => $this->target_id
                ? route('courses.index', ['categoryId' => $this->target_id, 'source' => $code])
                : (url('/').$ref),
            default => url('/').$ref,
        };
    }
}
