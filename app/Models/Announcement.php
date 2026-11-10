<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = [
        'title',
        'body',
        'status',
        'starts_at',
        'ends_at',
    ];

    public array $translatable = ['title', 'body'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function (Builder $builder) {
            $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        });
    }
}
