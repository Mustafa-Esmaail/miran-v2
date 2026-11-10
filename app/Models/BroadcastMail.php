<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastMail extends Model
{
    /** @use HasFactory<\Database\Factories\BroadcastMailFactory> */
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_FAILED = 'partially_failed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'subject',
        'body',
        'status',
        'created_by',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastMailRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
