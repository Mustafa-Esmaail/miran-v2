<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommentReply extends Model
{
    /** @use HasFactory<\Database\Factories\CommentReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'parent_reply_id',
        'user_id',
        'content',
        'approved',
        'hidden',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'bool',
            'hidden' => 'bool',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function parentReply(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_reply_id');
    }

    public function childReplies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_reply_id')->latest();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('approved', true)->where('hidden', false);
    }
}
