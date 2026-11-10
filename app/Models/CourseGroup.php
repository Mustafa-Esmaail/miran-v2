<?php

namespace App\Models;

use App\Enums\CourseGroupStatus;
use App\Models\Concerns\HasDeletedBy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class CourseGroup extends Model
{
    use HasDeletedBy, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Cart rows are polymorphic (no FK cascade), so they would otherwise point at a missing group.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $group): void {
            $group->cartItems()->delete();
        });
    }

    protected $fillable = [
        'course_id',
        'title',
        'group_price_amount',
        'start_at',
        'end_at',
        'capacity',
        'enrollment_deadline_at',
        'status',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'group_price_amount' => 'int',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'enrollment_deadline_at' => 'datetime',
            'status' => CourseGroupStatus::class,
        ];
    }

    protected function asDateTime($value): Carbon
    {
        try {
            return parent::asDateTime($value);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseGroupSession::class);
    }

    public function curriculumSections(): HasMany
    {
        return $this->hasMany(CourseCurriculumSection::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'purchasable');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'purchasable');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_group_instructors')
            ->using(CourseInstructor::class)
            ->withPivot('course_id');
    }

    /**
     * @return Builder<User>
     */
    public function instructorsQuery(): Builder
    {
        return User::query()
            ->join('course_group_instructors', 'course_group_instructors.user_id', '=', 'users.id')
            ->where('course_group_instructors.course_id', $this->course_id)
            ->where(function (Builder $query): void {
                $query->where('course_group_instructors.course_group_id', $this->id)
                    ->orWhereNull('course_group_instructors.course_group_id');
            })
            ->select('users.*')
            ->distinct();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CourseGroupStatus::Open);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->open()->where(function (Builder $query) {
            $query->whereNull('enrollment_deadline_at')
                ->orWhere('enrollment_deadline_at', '>', now());
        });
    }
}
