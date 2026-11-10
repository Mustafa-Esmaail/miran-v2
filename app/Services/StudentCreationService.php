<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderSource;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\StudentCredentialsNotification;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

class StudentCreationService
{
    public function __construct(private EnrollmentRepositoryInterface $enrollments) {}

    public function create(array $userData, int $courseId, int $courseGroupId): User
    {
        $result = $this->createWithEnrollment($userData, $courseId, $courseGroupId);

        return $result['user'];
    }

    /**
     * @return array{user: User, mail_attempted: bool, mail_sent: bool, mail_error: string|null}
     */
    private function createWithEnrollment(array $userData, int $courseId, int $courseGroupId): array
    {
        $plainPassword = (string) $userData['password'];

        $userData['status'] = User::STATUS_ACTIVE;
        $userData['password'] = Hash::needsRehash($userData['password'])
            ? Hash::make($userData['password'])
            : $userData['password'];

        $user = User::query()->create($userData);
        $user->assignRole($this->studentRole());

        $group = $this->resolveGroup($courseId, $courseGroupId);
        $this->createEnrollmentForGroup($user, $group);
        $mailResult = $this->sendCredentialsEmail($user, $plainPassword, true, $group);

        return [
            'user' => $user,
            'mail_attempted' => true,
            'mail_sent' => $mailResult['sent'],
            'mail_error' => $mailResult['error'],
        ];
    }

    /**
     * @return array{user: User, created: bool, enrolled: bool, mail_attempted: bool, mail_sent: bool, mail_error: string|null}
     */
    public function createOrEnroll(array $userData, int $courseId, int $courseGroupId): array
    {
        $group = $this->resolveGroup($courseId, $courseGroupId);
        $user = User::query()->where('email', $userData['email'])->first();
        $created = false;
        $plainPassword = filled($userData['password'] ?? null) ? (string) $userData['password'] : null;
        $mailAttempted = false;
        $mailSent = false;
        $mailError = null;

        if ($user) {
            $updates = collect($userData)
                ->except(['email', 'password'])
                ->filter(fn ($value): bool => $value !== null && $value !== '')
                ->all();

            if (! empty($updates)) {
                $updates['status'] = User::STATUS_ACTIVE;
                $user->fill($updates)->save();
            }

            if ($plainPassword !== null) {
                $user->forceFill([
                    'password' => Hash::make($plainPassword),
                ])->save();

                $mailResult = $this->sendCredentialsEmail($user, $plainPassword, false, $group);
                $mailAttempted = true;
                $mailSent = $mailResult['sent'];
                $mailError = $mailResult['error'];
            }

            if (! $user->hasRole('student')) {
                $user->assignRole($this->studentRole());
            }
        } else {
            $createResult = $this->createWithEnrollment($userData, $courseId, $courseGroupId);
            $user = $createResult['user'];
            $created = true;
            $mailAttempted = $createResult['mail_attempted'];
            $mailSent = $createResult['mail_sent'];
            $mailError = $createResult['mail_error'];
        }

        if ($created) {
            return [
                'user' => $user,
                'created' => true,
                'enrolled' => true,
                'mail_attempted' => $mailAttempted,
                'mail_sent' => $mailSent,
                'mail_error' => $mailError,
            ];
        }

        $existingEnrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $group->course_id)
            ->where('course_group_id', $group->id)
            ->exists();

        if ($existingEnrollment) {
            return [
                'user' => $user,
                'created' => false,
                'enrolled' => false,
                'mail_attempted' => $mailAttempted,
                'mail_sent' => $mailSent,
                'mail_error' => $mailError,
            ];
        }

        $this->createEnrollmentForGroup($user, $group);

        return [
            'user' => $user,
            'created' => false,
            'enrolled' => true,
            'mail_attempted' => $mailAttempted,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError,
        ];
    }

    private function resolveGroup(int $courseId, int $courseGroupId): CourseGroup
    {
        return CourseGroup::query()
            ->where('course_id', $courseId)
            ->whereKey($courseGroupId)
            ->firstOrFail();
    }

    private function studentRole(): Role
    {
        return Role::findOrCreate('student', 'web');
    }

    private function createEnrollmentForGroup(User $user, CourseGroup $group): void
    {
        $priceAmount = (int) ($group->course?->price ?? 0);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ADMIN-'.Str::upper(Str::random(8)),
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => $priceAmount,
            'discount_amount' => 0,
            'total_amount' => $priceAmount,
            'notes' => 'Admin enrollment',
            'source' => OrderSource::Admin->value,
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
            'currency' => 'SAR',
            'price_amount' => $priceAmount,
            'quantity' => 1,
        ]);

        $attributes = [
            'course_id' => $group->course_id,
            'order_item_id' => $orderItem->id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ];

        if ($this->enrollments->restoreTrashedForUserCourseGroup($user->id, $group->id, $attributes)) {
            return;
        }

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_group_id' => $group->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array{sent: bool, error: string|null}
     */
    private function sendCredentialsEmail(User $user, string $plainPassword, bool $isNewAccount, ?CourseGroup $group = null): array
    {
        try {
            $course = $group?->course;
            $courseTitle = $course?->getTranslation('title', app()->getLocale());
            $courseImageUrl = $course?->image_url;
            $enrollmentDate = now()->format('Y-m-d H:i');

            $user->notify(new StudentCredentialsNotification(
                plainPassword: $plainPassword,
                isNewAccount: $isNewAccount,
                courseTitle: is_string($courseTitle) ? $courseTitle : null,
                courseImageUrl: $courseImageUrl,
                enrollmentDate: $enrollmentDate,
            ));

            return ['sent' => true, 'error' => null];
        } catch (Throwable $exception) {
            try {
                report($exception);
            } catch (Throwable) {
                // swallow logging/reporting failures to avoid breaking user flows
            }

            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }
}
