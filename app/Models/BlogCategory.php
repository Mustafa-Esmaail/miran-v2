<?php

namespace App\Models;

use App\Enums\BlogCategoryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class BlogCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'title',
        'slug',
        'status',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'status' => BlogCategoryStatus::class,
        ];
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BlogCategoryStatus::Active);
    }
}
