<?php

namespace App\Models;

use App\Enums\AttachmentVisibility;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\CourseTemplate;
use App\Enums\DeliveryType;
use App\Models\Concerns\HasDeletedBy;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasDeletedBy, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Wishlist rows are polymorphic (no FK cascade), so they would otherwise point at a missing course.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $course): void {
            $course->wishlistItems()->delete();
        });
    }

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

    public function getIntroVideoUrlAttribute(): ?string
    {
        $path = $this->intro_video_path;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }

    protected $fillable = [
        'category_id',
        'title',
        'summary',
        'description',
        'timeline_items',
        'timeline_highlights',
        'slug',
        'image_path',
        'intro_video_path',
        'sneak_peek_images',
        'price',
        'is_free',
        'is_featured',
        'sort_order',
        'start_date',
        'source_url',
        'sku',
        'duration_hours',
        'duration_group',
        'days_count',
        'time_from',
        'time_to',
        'delivery_type', // ONLINE OR OFFLINE
        'course_level',
        'status',
        'template',
        'template_content',
        'accreditation_number',
    ];

    public array $translatable = ['title', 'summary', 'description'];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'template' => CourseTemplate::class,
            'template_content' => 'array',
            'price' => 'decimal:2',
            'is_free' => 'bool',
            'is_featured' => 'bool',
            'sort_order' => 'int',
            'start_date' => 'date',
            'duration_hours' => 'int',
            'days_count' => 'int',
            'time_from' => 'datetime:H:i',
            'time_to' => 'datetime:H:i',
            'delivery_type' => DeliveryType::class,
            'course_level' => CourseLevel::class,
            'sneak_peek_images' => 'array',
            'timeline_items' => 'array',
            'timeline_highlights' => 'array',
        ];
    }

    public function getSneakPeekImageUrlsAttribute(): array
    {
        $images = $this->sneak_peek_images ?? [];

        return collect($images)
            ->filter()
            ->map(function (string $path): string {
                if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
                    return $path;
                }

                return S3Url::temporary($path);
            })
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(CourseGroup::class);
    }

    /**
     * The nearest open group starting today or later (the landing pages' "next batch").
     */
    public function nextGroup(): ?CourseGroup
    {
        return $this->groups()
            ->active()
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at')
            ->first();
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_courses')->withTimestamps();
    }

    public function wishlistedBy(): MorphToMany
    {
        return $this->morphToMany(User::class, 'wishable', 'wishlist_items')->withTimestamps();
    }

    public function wishlistItems(): MorphMany
    {
        return $this->morphMany(WishlistItem::class, 'wishable');
    }

    public function landingPages(): MorphMany
    {
        return $this->morphMany(Page::class, 'landable');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function curriculumSections(): HasMany
    {
        return $this->hasMany(CourseCurriculumSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CourseAttachment::class);
    }

    /**
     * Course-wide attachments shown to enrolled students of every group.
     */
    public function generalAttachments(): HasMany
    {
        return $this->hasMany(CourseAttachment::class)
            ->where('visibility', AttachmentVisibility::General)
            ->orderBy('id');
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(CourseGroupSession::class, CourseGroup::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseQuestion::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_group_instructors')
            ->withPivot('course_group_id')
            ->withTimestamps();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'bundle_courses')->withTimestamps();
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $builder) use ($term) {
            $builder->where('title->en', 'like', "%{$term}%")
                ->orWhere('title->ar', 'like', "%{$term}%")
                ->orWhere('summary->en', 'like', "%{$term}%")
                ->orWhere('summary->ar', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhereHas('category', function (Builder $categoryQuery) use ($term) {
                    $categoryQuery->where('name->en', 'like', "%{$term}%")
                        ->orWhere('name->ar', 'like', "%{$term}%");
                })
                ->orWhereHas('trainers', function (Builder $trainerQuery) use ($term) {
                    $trainerQuery->where('name', 'like', "%{$term}%");
                });
        });
    }

    public function getFinalPriceAttribute(): ?float
    {
        if ($this->is_free) {
            return 0.0;
        }

        $price = $this->price;
        if ($price === null) {
            return null;
        }

        return $this->calculateDiscountedPrice((float) $price);
    }

    public function getBestDiscountAmount(float $price): float
    {
        $discounts = $this->relationLoaded('discounts') ? $this->discounts : $this->discounts()->get();

        $best = 0.0;
        foreach ($discounts as $discount) {
            if (! $discount->is_active) {
                continue;
            }

            $amount = $discount->calculateDiscountAmount($price);
            if ($amount > $best) {
                $best = $amount;
            }
        }

        return $best;
    }

    public function calculateDiscountedPrice(float $price): float
    {
        if ($this->is_free) {
            return 0.0;
        }

        $discountAmount = $this->getBestDiscountAmount($price);

        return max(0, $price - $discountAmount);
    }
}
