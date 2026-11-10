<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'database_enabled',
        'announcements_enabled',
        'course_updates_enabled',
        'comment_replies_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'bool',
            'database_enabled' => 'bool',
            'announcements_enabled' => 'bool',
            'course_updates_enabled' => 'bool',
            'comment_replies_enabled' => 'bool',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
