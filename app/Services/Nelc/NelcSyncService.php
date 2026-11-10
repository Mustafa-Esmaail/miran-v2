<?php

namespace App\Services\Nelc;

use App\Enums\NelcSyncStatus;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\NelcSyncLog;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NelcSyncService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function syncRegistered(CertificateRequest $certificateRequest): void
    {
        $courseGroup = $certificateRequest->courseGroup;
        $course = $courseGroup?->course;
        $user = $certificateRequest->user;

        if (! $course || ! $user) {
            Log::channel('nelc')->warning('Registered: cannot sync — certificate request has no resolvable course/user', [
                'certificate_request_id' => $certificateRequest->id,
            ]);

            return;
        }

        if (! $this->shouldProceed('Registered', $user->id, $course->id, dedup: true)) {
            return;
        }

        $identity = $this->resolveIdentity($user, $certificateRequest);
        $instructor = $this->resolveInstructor($course, $courseGroup);
        $lang = $this->languageTag();

        $payload = [
            'actor' => [
                'mbox' => 'mailto:'.$identity['email'],
                'name' => $identity['name'],
                'objectType' => 'Agent',
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/registered',
                'display' => ['en-US' => 'registered'],
            ],
            'object' => [
                'id' => $this->absoluteRoute('courses.show', $course->slug),
                'definition' => [
                    'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                    'description' => [$lang => $course->getTranslation('description', app()->getLocale())],
                    'type' => 'https://w3id.org/xapi/cmi5/activitytype/course',
                ],
                'objectType' => 'Activity',
            ],
            'context' => [
                'instructor' => [
                    'name' => $instructor['name'],
                    'mbox' => 'mailto:'.$instructor['email'],
                ],
                'platform' => $this->platformKey(),
                'language' => $lang,
                'extensions' => [
                    'https://nelc.gov.sa/extensions/duration' => $this->courseDuration($course),
                    'https://nelc.gov.sa/extensions/lms_url' => $this->settings->siteUrl(),
                    'https://nelc.gov.sa/extensions/program_url' => $this->absoluteRoute('courses.show', $course->slug),
                    'https://nelc.gov.sa/extensions/learner_mobile_no' => $identity['mobile'],
                    'https://nelc.gov.sa/extensions/learner_full_name' => $identity['fullName'],
                    'https://nelc.gov.sa/extensions/learner_nationality' => $identity['nationality'],
                    'https://nelc.gov.sa/extensions/date_of_birth' => $identity['dateOfBirth'],
                    'https://nelc.gov.sa/extensions/platform' => $this->platformExtension(),
                ],
            ],
            'timestamp' => $this->timestamp(),
        ];

        $this->send('Registered', $user, $course, $courseGroup, $certificateRequest, $payload);
    }

    public function syncInitialized(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        $courseGroup = $enrollment->courseGroup;
        $user = $enrollment->user;

        if (! $course || ! $user) {
            Log::channel('nelc')->warning('Initialized: cannot sync — enrollment has no resolvable course/user', [
                'enrollment_id' => $enrollment->id,
            ]);

            return;
        }

        if (! $this->shouldProceed('Initialized', $user->id, $course->id, dedup: true)) {
            return;
        }

        $certificateRequest = $this->certificateRequestFor($user->id, $courseGroup?->id);
        $identity = $this->resolveIdentity($user, $certificateRequest);
        $instructor = $this->resolveInstructor($course, $courseGroup);
        $lang = $this->languageTag();

        $payload = [
            'actor' => [
                'name' => $identity['name'],
                'mbox' => 'mailto:'.$identity['email'],
                'objectType' => 'Agent',
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/initialized',
                'display' => ['en-US' => 'initialized'],
            ],
            'object' => [
                'id' => $this->absoluteRoute('courses.show', $course->slug),
                'definition' => [
                    'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                    'description' => [$lang => ''],
                    'type' => 'https://w3id.org/xapi/cmi5/activitytype/course',
                ],
                'objectType' => 'Activity',
            ],
            'context' => [
                'instructor' => [
                    'name' => $instructor['name'],
                    'mbox' => 'mailto:'.$instructor['email'],
                ],
                'platform' => $this->platformKey(),
                'language' => $lang,
                'extensions' => [
                    'https://nelc.gov.sa/extensions/platform' => $this->platformExtension(),
                ],
            ],
            'timestamp' => $this->timestamp(),
        ];

        $this->send('Initialized', $user, $course, $courseGroup, $enrollment, $payload);
    }

    public function syncAttempted(QuizAttempt $attempt): void
    {
        $quiz = $attempt->quiz;
        $course = $quiz?->course;
        $user = $attempt->user;

        if (! $quiz || ! $course || ! $user) {
            Log::channel('nelc')->warning('Attempted: cannot sync — quiz attempt has no resolvable quiz/course/user', [
                'quiz_attempt_id' => $attempt->id,
            ]);

            return;
        }

        if (! $this->shouldProceed('Attempted', $user->id, $course->id, dedup: false)) {
            return;
        }

        $courseGroup = $quiz->courseGroup;
        $certificateRequest = $this->certificateRequestFor($user->id, $courseGroup?->id);
        $identity = $this->resolveIdentity($user, $certificateRequest);
        $instructor = $this->resolveInstructor($course, $courseGroup);
        $lang = $this->languageTag();

        $totalPoints = $attempt->totalPoints();
        $scaled = $totalPoints > 0 ? round($attempt->score / $totalPoints, 2) : 0.0;

        $payload = [
            'actor' => [
                'name' => $identity['name'],
                'mbox' => 'mailto:'.$identity['email'],
                'objectType' => 'Agent',
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/attempted',
                'display' => ['en-US' => 'attempted'],
            ],
            'object' => [
                'id' => $this->absoluteRoute('dashboard.course-player.quiz', [$course->slug, $courseGroup?->id ?? 0, $quiz->id]),
                'definition' => [
                    'name' => [$lang => $quiz->title],
                    'description' => [$lang => ''],
                    'type' => 'http://id.tincanapi.com/activitytype/unit-test',
                ],
                'objectType' => 'Activity',
            ],
            'context' => [
                'instructor' => [
                    'name' => $instructor['name'],
                    'mbox' => 'mailto:'.$instructor['email'],
                ],
                'platform' => $this->platformKey(),
                'language' => $lang,
                'extensions' => [
                    'http://id.tincanapi.com/extension/attempt-id' => (string) $attempt->getAttemptNumber(),
                    'https://nelc.gov.sa/extensions/platform' => $this->platformExtension(),
                ],
                'contextActivities' => [
                    'parent' => [
                        [
                            'id' => $this->absoluteRoute('courses.show', $course->slug),
                            'definition' => [
                                'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                                'description' => [$lang => ''],
                                'type' => 'https://w3id.org/xapi/cmi5/activitytype/course',
                            ],
                            'objectType' => 'Activity',
                        ],
                    ],
                ],
            ],
            'result' => [
                'score' => [
                    'scaled' => $scaled,
                    'raw' => $attempt->score,
                    'min' => 0,
                    'max' => $totalPoints,
                ],
                'completion' => true,
                'success' => $attempt->isPassed(),
            ],
            'timestamp' => $this->timestamp(),
        ];

        $this->send('Attempted', $user, $course, $courseGroup, $attempt, $payload);
    }

    public function syncEarned(Certificate $certificate): void
    {
        $course = $certificate->course;
        $courseGroup = $certificate->courseGroup;
        $user = $certificate->user;

        if (! $course || ! $user) {
            Log::channel('nelc')->warning('Earned: cannot sync — certificate has no resolvable course/user', [
                'certificate_id' => $certificate->id,
            ]);

            return;
        }

        if (! $this->shouldProceed('Earned', $user->id, $course->id, dedup: true)) {
            return;
        }

        $certificateRequest = $this->certificateRequestFor($user->id, $courseGroup?->id);
        $identity = $this->resolveIdentity($user, $certificateRequest);
        $lang = $this->languageTag();
        $certUrl = $certificate->file_path ? S3Url::temporary($certificate->file_path) : $this->settings->siteUrl().'/dashboard/certificates';

        $payload = [
            'actor' => [
                'name' => $identity['name'],
                'mbox' => 'mailto:'.$identity['email'],
                'objectType' => 'Agent',
            ],
            'verb' => [
                'id' => 'http://id.tincanapi.com/verb/earned',
                'display' => ['en-US' => 'earned'],
            ],
            'object' => [
                'id' => $this->settings->siteUrl().'/certificates/'.$certificate->id,
                'definition' => [
                    'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                    'type' => 'https://www.opigno.org/en/tincan_registry/activity_type/certificate',
                ],
                'objectType' => 'Activity',
            ],
            'context' => [
                'extensions' => [
                    'http://id.tincanapi.com/extension/jws-certificate-location' => $certUrl,
                    'https://nelc.gov.sa/extensions/platform' => $this->platformExtension(),
                ],
                'platform' => $this->platformKey(),
                'language' => $lang,
                'contextActivities' => [
                    'parent' => [
                        [
                            'id' => $this->absoluteRoute('courses.show', $course->slug),
                            'definition' => [
                                'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                                'description' => [$lang => ''],
                                'type' => 'https://w3id.org/xapi/cmi5/activitytype/course',
                            ],
                            'objectType' => 'Activity',
                        ],
                    ],
                ],
            ],
            'timestamp' => $this->timestamp(),
        ];

        $this->send('Earned', $user, $course, $courseGroup, $certificate, $payload);
    }

    public function syncRated(CourseReview $review): void
    {
        $course = $review->course;
        $user = $review->user;

        if (! $course || ! $user) {
            Log::channel('nelc')->warning('Rated: cannot sync — course review has no resolvable course/user', [
                'course_review_id' => $review->id,
            ]);

            return;
        }

        if (! $this->shouldProceed('Rated', $user->id, $course->id, dedup: false)) {
            return;
        }

        $identity = $this->resolveIdentity($user, $this->certificateRequestFor($user->id, null));
        $instructor = $this->resolveInstructor($course, null);
        $lang = $this->languageTag();

        $payload = [
            'actor' => [
                'name' => $identity['name'],
                'mbox' => 'mailto:'.$identity['email'],
                'objectType' => 'Agent',
            ],
            'verb' => [
                'id' => 'http://id.tincanapi.com/verb/rated',
                'display' => ['en-US' => 'rated'],
            ],
            'object' => [
                'id' => $this->absoluteRoute('courses.show', $course->slug),
                'definition' => [
                    'name' => [$lang => $course->getTranslation('title', app()->getLocale())],
                    'description' => [$lang => ''],
                    'type' => 'https://w3id.org/xapi/cmi5/activitytype/course',
                ],
                'objectType' => 'Activity',
            ],
            'context' => [
                'instructor' => [
                    'name' => $instructor['name'],
                    'mbox' => 'mailto:'.$instructor['email'],
                ],
                'platform' => $this->platformKey(),
                'language' => $lang,
                'extensions' => [
                    'https://nelc.gov.sa/extensions/platform' => $this->platformExtension(),
                ],
            ],
            'result' => [
                'score' => [
                    'scaled' => round($review->rating / 5, 2),
                    'raw' => $review->rating,
                    'min' => 0,
                    'max' => 5,
                ],
                'response' => (string) $review->content,
            ],
            'timestamp' => $this->timestamp(),
        ];

        $this->send('Rated', $user, $course, null, $review, $payload);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->settings->nelcConfig()['enabled'];
    }

    private function shouldProceed(string $eventType, int $userId, int $courseId, bool $dedup): bool
    {
        if (! $this->isEnabled()) {
            Log::channel('nelc')->debug("{$eventType}: sync disabled, skipping", [
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            return false;
        }

        if ($dedup && $this->alreadySent($eventType, $userId, $courseId)) {
            Log::channel('nelc')->debug("{$eventType}: already sent, skipping duplicate", [
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            return false;
        }

        return true;
    }

    private function alreadySent(string $eventType, int $userId, int $courseId): bool
    {
        return NelcSyncLog::query()
            ->where('event_type', $eventType)
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', NelcSyncStatus::Sent)
            ->exists();
    }

    private function send(string $eventType, User $user, Course $course, ?CourseGroup $courseGroup, Model $reference, array $payload): void
    {
        $config = $this->settings->nelcConfig();

        try {
            $response = Http::withBasicAuth($config['lrs_username'], $config['lrs_password'])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Experience-API-Version' => '1.0.3',
                ])
                ->post($config['lrs_endpoint'], $payload);

            $successful = $response->successful();

            NelcSyncLog::query()->create([
                'event_type' => $eventType,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_group_id' => $courseGroup?->id,
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
                'payload' => $payload,
                'status' => $successful ? NelcSyncStatus::Sent : NelcSyncStatus::Failed,
                'response' => (string) $response->body(),
                'attempts' => 1,
                'synced_at' => $successful ? now() : null,
            ]);

            $context = ['user_id' => $user->id, 'course_id' => $course->id, 'status' => $response->status()];

            if ($successful) {
                Log::channel('nelc')->info("{$eventType}: sent successfully", $context);
            } else {
                Log::channel('nelc')->warning("{$eventType}: LRS rejected the statement", $context + [
                    'response' => $response->json() ?? (string) $response->body(),
                ]);
            }
        } catch (Throwable $exception) {
            NelcSyncLog::query()->create([
                'event_type' => $eventType,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_group_id' => $courseGroup?->id,
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
                'payload' => $payload,
                'status' => NelcSyncStatus::Failed,
                'response' => substr($exception->getMessage(), 0, 2000),
                'attempts' => 1,
                'synced_at' => null,
            ]);

            Log::channel('nelc')->error("{$eventType}: request to LRS failed", [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function certificateRequestFor(int $userId, ?int $courseGroupId): ?CertificateRequest
    {
        if (! $courseGroupId) {
            return CertificateRequest::query()->where('user_id', $userId)->first();
        }

        return CertificateRequest::query()
            ->where('user_id', $userId)
            ->where('course_group_id', $courseGroupId)
            ->first();
    }

    /**
     * @return array{name:string,email:string,mobile:string,fullName:string,nationality:string,dateOfBirth:string}
     */
    private function resolveIdentity(User $user, ?CertificateRequest $certificateRequest): array
    {
        $locale = app()->getLocale();

        $fullName = $user->name;
        if ($certificateRequest) {
            $fullName = $locale === 'ar'
                ? trim(implode(' ', array_filter([
                    $certificateRequest->first_name_ar,
                    $certificateRequest->second_name_ar,
                    $certificateRequest->third_name_ar,
                    $certificateRequest->last_name_ar,
                ])))
                : trim(implode(' ', array_filter([
                    $certificateRequest->first_name_en,
                    $certificateRequest->second_name_en,
                    $certificateRequest->third_name_en,
                    $certificateRequest->last_name_en,
                ])));
        }

        return [
            'name' => $certificateRequest?->id_number ?: ($user->national_id ?: ''),
            'email' => $certificateRequest?->email ?: ($user->email ?: ''),
            'mobile' => $certificateRequest?->phone ?: ($user->phone ?: ''),
            'fullName' => $fullName ?: '',
            'nationality' => $certificateRequest?->nationality ?: ($user->nationality ?: ''),
            'dateOfBirth' => $certificateRequest?->birth_date?->format('Y-m-d') ?? $user->birth_date?->format('Y-m-d') ?? '',
        ];
    }

    /**
     * @return array{name:string,email:string}
     */
    private function resolveInstructor(Course $course, ?CourseGroup $courseGroup): array
    {
        $instructor = $courseGroup?->instructorsQuery()->first() ?? $course->trainers()->first();

        return [
            'name' => $instructor?->name ?? '',
            'email' => $instructor?->email ?? '',
        ];
    }

    private function courseDuration(Course $course): string
    {
        $hours = (int) ($course->duration_hours ?? 0);

        return 'PT'.$hours.'H';
    }

    private function languageTag(): string
    {
        return app()->getLocale() === 'ar' ? 'ar-SA' : 'en-US';
    }

    private function platformKey(): string
    {
        return (string) config('app.name');
    }

    /**
     * Builds an absolute URL from the canonical site URL setting instead of the
     * ambient request root, so statements sent to the LRS stay correct even when
     * the request reached the app via an internal host/IP (e.g. 127.0.0.1).
     *
     * @param  array<int|string, mixed>|int|string  $parameters
     */
    private function absoluteRoute(string $name, array|int|string $parameters = []): string
    {
        return $this->settings->siteUrl().route($name, $parameters, absolute: false);
    }

    /**
     * @return array{name: array{'ar-SA': string, 'en-US': string}}
     */
    private function platformExtension(): array
    {
        $config = $this->settings->nelcConfig();

        return [
            'name' => [
                'ar-SA' => $config['platform_ar'],
                'en-US' => $config['platform_en'],
            ],
        ];
    }

    private function timestamp(): string
    {
        return now()->format('Y-m-d\TH:i:s.v\Z');
    }
}
