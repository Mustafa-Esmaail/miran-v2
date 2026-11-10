<?php

namespace App\Models;

use App\Enums\BlogStatus;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    use HasFactory, HasTranslations;

    public function getImagePathAttribute(): ?string
    {
        return $this->attributes['image_path'] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'short_description',
        'long_description',
        'image_path',
        'author_id',
        'category_id',
        'status',
        'published_at',
    ];

    public array $translatable = ['title', 'content', 'short_description', 'long_description'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'status' => BlogStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', BlogStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());
    }
}
