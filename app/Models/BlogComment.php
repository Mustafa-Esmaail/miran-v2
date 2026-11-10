<?php

namespace App\Models;

use App\Enums\BlogCommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class BlogComment extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'blog_id',
        'user_id',
        'content',
        'status',
    ];

    public array $translatable = ['content'];

    protected function casts(): array
    {
        return [
            'status' => BlogCommentStatus::class,
        ];
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', BlogCommentStatus::Approved);
    }
}
