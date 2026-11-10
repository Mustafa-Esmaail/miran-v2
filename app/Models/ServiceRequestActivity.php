<?php

namespace App\Models;

use App\Enums\ServiceRequestActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestActivity extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestActivityFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'actor_user_id',
        'type',
        'from_value',
        'to_value',
        'note',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServiceRequestActivityType::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
