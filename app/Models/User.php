<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\UserPasswordResetNotification;
use App\Support\S3Url;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Impersonate, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DELETED = 'deleted';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'phone',
        'national_id',
        'nationality',
        'birth_date',
        'gender',
        'about',
        'avatar_path',
        'cv_image_path',
        'status',
        'is_chat_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_chat_banned' => 'boolean',
        ];
    }

    public function isChatBanned(): bool
    {
        return (bool) $this->is_chat_banned;
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teachingCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_group_instructors')
            ->withPivot('course_group_id')
            ->withTimestamps();
    }

    public function instructorGroups(): BelongsToMany
    {
        return $this->belongsToMany(CourseGroup::class, 'course_group_instructors');
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function wishlistCourses(): MorphToMany
    {
        return $this->morphedByMany(Course::class, 'wishable', 'wishlist_items')->withTimestamps();
    }

    public function wishlistBundles(): MorphToMany
    {
        return $this->morphedByMany(Bundle::class, 'wishable', 'wishlist_items')->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function courseReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function blogComments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'author_id');
    }

    public function courseQuestions(): HasMany
    {
        return $this->hasMany(CourseQuestion::class);
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class);
    }

    public function questionVotes(): HasMany
    {
        return $this->hasMany(QuestionVote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function commentReplies(): HasMany
    {
        return $this->hasMany(CommentReply::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function bankTransferReceipts(): HasMany
    {
        return $this->hasMany(BankTransferReceipt::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(\App\Models\ServiceRequest::class);
    }

    public function purchasedSeats(): HasMany
    {
        return $this->hasMany(OrderItemSeat::class, 'purchaser_user_id');
    }

    public function invitedSeats(): HasMany
    {
        return $this->hasMany(OrderItemSeat::class, 'invited_user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canImpersonate(): bool
    {
        return $this->can('students.impersonate');
    }

    public function canBeImpersonated(): bool
    {
        return $this->roles()->where('guard_name', 'admin')->doesntExist();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new UserPasswordResetNotification($token));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->avatar_path;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }

    public function getCvImageUrlAttribute(): ?string
    {
        return $this->resolveFileUrl($this->cv_image_path);
    }

    private function resolveFileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }
}
