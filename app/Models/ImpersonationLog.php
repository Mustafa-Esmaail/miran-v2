<?php

namespace App\Models;

use App\Enums\ImpersonationEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'student_id',
        'event_type',
        'route_name',
        'url',
        'method',
        'meta',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => ImpersonationEventType::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
