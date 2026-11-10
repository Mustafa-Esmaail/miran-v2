<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasTranslations;

    /** Templates whose page is attached to a course or bundle (the "landable"). */
    public const LANDABLE_TEMPLATES = ['landing-page', 'landing-v2', 'landing-excel', 'landing-kpi', 'landing-powerbi', 'course-profile'];

    /**
     * Landing templates tracked as versions: each page is one version of the
     * "landing line" its landing_key (the slug ads point at) names.
     */
    public const VERSIONED_TEMPLATES = ['landing-v2', 'landing-excel', 'landing-kpi', 'landing-powerbi'];

    protected $fillable = [
        'title',
        'slug',
        'landing_key',
        'version',
        'version_name',
        'marketing_source',
        'body',
        'template',
        'landable_type',
        'landable_id',
        'is_published',
        'published_at',
        'drafted_at',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_image',
        'cta_label',
    ];

    public array $translatable = ['title', 'body', 'meta_title', 'meta_description', 'cta_label'];

    protected function casts(): array
    {
        return [
            'is_published' => 'bool',
            'is_active' => 'bool',
            'version' => 'integer',
            'published_at' => 'datetime',
            'drafted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // A landing page starts as v1 of its own line until it is published as a new version of another one.
        static::saving(function (Page $page): void {
            if (! $page->isVersioned()) {
                return;
            }

            if (blank($page->landing_key)) {
                $page->landing_key = $page->slug;
                $page->version ??= 1;
                $page->published_at ??= now();

                return;
            }

            // Renaming the live page in the editor moves its landing line along with it.
            if ($page->isDirty('slug')
                && ! $page->isDirty('landing_key')
                && $page->drafted_at === null
                && $page->landing_key === $page->getOriginal('slug')) {
                $page->landing_key = $page->slug;
            }
        });
    }

    public function isVersioned(): bool
    {
        return in_array($this->template, self::VERSIONED_TEMPLATES, true);
    }

    public function isLiveVersion(): bool
    {
        return $this->isVersioned() && $this->drafted_at === null && $this->slug === $this->landing_key;
    }

    public function versionLabel(): string
    {
        return filled($this->version_name) ? $this->version_name : 'v'.($this->version ?? 1);
    }

    public function landable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ctaLabel(string $locale): string
    {
        $label = trim((string) $this->getTranslation('cta_label', $locale));

        return $label !== '' ? $label : __('web.course_profile.default_cta_label', [], $locale);
    }

    public function ctaUrl(): ?string
    {
        return match (true) {
            $this->landable instanceof Course => route('courses.show', $this->landable->slug),
            $this->landable instanceof Bundle => route('bundles.select-groups', ['bundle' => $this->landable->slug]),
            default => null,
        };
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
