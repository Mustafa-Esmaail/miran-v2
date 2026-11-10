<?php

namespace App\Services;

use App\Enums\ServiceRequestSource;
use App\Exceptions\BlockedPhoneException;
use App\Exceptions\DuplicateServiceRequestException;
use App\Exceptions\TooManyServiceRequestsException;
use App\Http\Middleware\CaptureUtm;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use App\Models\User;
use App\Support\SheetRowContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ServiceRequestService
{
    private const MAX_SUBMISSIONS_PER_WINDOW = 5;

    private const RATE_LIMIT_DECAY_SECONDS = 600;

    /** Saudi mobile, 9 national digits (after normalizeLocalPhone). */
    public const LOCAL_PHONE_PATTERN = '/^5\d{8}$/';

    public function __construct(
        private readonly ServiceRequestActivityService $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws DuplicateServiceRequestException
     */
    public function createFromForm(array $payload, ServiceRequestSource $source = ServiceRequestSource::Form, ?int $userId = null): ServiceRequest
    {
        return DB::transaction(function () use ($payload, $source, $userId) {
            $courseId = Arr::get($payload, 'course_id');
            $courseGroupId = $this->resolveCourseGroupId($courseId);
            $typeId = Arr::get($payload, 'type_id');
            $phone = self::normalizePhone((string) Arr::get($payload, 'phone'));
            $isSuspectedBot = (bool) Arr::get($payload, 'is_suspected_bot', false);

            if (! $isSuspectedBot) {
                $this->guardAgainstBlockedPhone($phone);
                $this->guardAgainstDuplicateToday($phone, Arr::get($payload, 'ip_address'));
                // $this->guardAgainstRateLimit(Arr::get($payload, 'ip_address'), $phone, Arr::get($payload, 'name'), Arr::get($payload, 'user_agent'));
            }

            $request = ServiceRequest::query()->create([
                'source' => $source,
                'type_id' => $typeId,
                'marketing_source' => session('marketing_source'),
                ...$this->utmAttributes(),
                'user_id' => $this->resolveUserId($userId),
                'name' => Arr::get($payload, 'name'),
                'phone' => $phone,
                'email' => Arr::get($payload, 'email'),
                'ip_address' => Arr::get($payload, 'ip_address'),
                'user_agent' => Arr::get($payload, 'user_agent'),
                'message' => Arr::get($payload, 'message'),
                'course_id' => $courseId,
                'course_group_id' => $courseGroupId,
                'status' => $this->resolveInitialStatus($isSuspectedBot),
                'team_id' => $this->resolveOwnerTeamId($source),
                'priority' => Arr::get($payload, 'priority', 'normal'),
                'last_activity_at' => now(),
            ]);

            if ($isSuspectedBot) {
                $this->logBotDetection('honeypot', $request);
            }

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws DuplicateServiceRequestException
     */
    public function createFromLeadForm(array $payload, ServiceRequestSource $source, ?int $userId = null, ?string $sectionType = null, ?string $landingSlug = null): ServiceRequest
    {
        return DB::transaction(function () use ($payload, $source, $userId, $sectionType, $landingSlug) {
            $source = $this->resolveLeadFormSource($source, $sectionType);
            $courseId = Arr::get($payload, 'course_id');
            $courseGroupId = $this->resolveCourseGroupId($courseId);
            $phone = self::normalizePhone((string) Arr::get($payload, 'phone'));
            $isSuspectedBot = (bool) Arr::get($payload, 'is_suspected_bot', false);

            if (! $isSuspectedBot) {
                $this->guardAgainstBlockedPhone($phone);
                $this->guardAgainstDuplicateToday($phone, Arr::get($payload, 'ip_address'));
                // $this->guardAgainstRateLimit(Arr::get($payload, 'ip_address'), $phone, Arr::get($payload, 'name'), Arr::get($payload, 'user_agent'));
            }

            // The observer builds the sheet row during create(); hand it the landing slug
            // for that call only (not stored on the request).
            $sheetContext = app(SheetRowContext::class);
            $sheetContext->setLandingSlug($landingSlug);

            try {
                $request = ServiceRequest::query()->create([
                    'source' => $source,
                    'type_id' => Arr::get($payload, 'type_id'),
                    'marketing_source' => session('marketing_source'),
                    ...$this->utmAttributes(),
                    'user_id' => $this->resolveUserId($userId),
                    'name' => Arr::get($payload, 'name'),
                    'phone' => $phone,
                    'email' => Arr::get($payload, 'email'),
                    'ip_address' => Arr::get($payload, 'ip_address'),
                    'user_agent' => Arr::get($payload, 'user_agent'),
                    'message' => Arr::get($payload, 'message'),
                    'course_id' => $courseId,
                    'course_group_id' => $courseGroupId,
                    'bundle_id' => Arr::get($payload, 'bundle_id'),
                    'page_id' => Arr::get($payload, 'page_id'),
                    'status' => $this->resolveInitialStatus($isSuspectedBot),
                    'team_id' => $this->resolveOwnerTeamId($source),
                    'priority' => Arr::get($payload, 'priority', 'normal'),
                    'last_activity_at' => now(),
                ]);
            } finally {
                $sheetContext->clear();
            }

            if ($isSuspectedBot) {
                $this->logBotDetection('honeypot', $request);
            }

            return $request;
        });
    }

    /**
     * UTMs of the ad click that brought the visitor, captured by CaptureUtm.
     *
     * @return array<string, ?string>
     */
    private function utmAttributes(): array
    {
        $utm = (array) session('utm', []);

        return collect(CaptureUtm::KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $utm[$key] ?? null])
            ->all();
    }

    private function resolveInitialStatus(bool $isSuspectedBot): ?string
    {
        return $isSuspectedBot
            ? ServiceRequestStatus::spamBucket()?->slug
            : ServiceRequestStatus::default()?->slug;
    }

    /**
     * Guards against a stale auth session (e.g. the user row was deleted after
     * login) crashing the insert on the user_id foreign key.
     */
    private function resolveUserId(?int $userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        return User::query()->whereKey($userId)->exists() ? $userId : null;
    }

    /**
     * Collapses any phone format (with/without country code, spaces, dashes, "+",
     * leading "0") to a consistent international-format string, e.g. "966555555555",
     * so the same real number is always stored/compared the same way regardless of
     * which form (and which country-code prefix) it was submitted through.
     * Arabic-Indic digits are converted to Latin first so they aren't stripped.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', self::toLatinDigits($phone)) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '966'.$digits;
    }

    /**
     * The ways one number is commonly stored ("966…", "+966…", "00966…", "0…", national digits),
     * so a lookup finds a lead whatever form or person entered it.
     *
     * @return array<int, string>
     */
    public static function phoneVariants(string $phone): array
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === '') {
            return [];
        }

        $national = substr($normalized, 3);

        return array_values(array_unique(array_filter([
            trim($phone),
            $normalized,
            '+'.$normalized,
            '00'.$normalized,
            '0'.$national,
            $national,
        ])));
    }

    /**
     * Existing leads with the same phone, newest first — for a non-blocking warning
     * in the admin form (the public forms keep their hard 12-hour guard).
     *
     * @return Collection<int, ServiceRequest>
     */
    public static function findPossibleDuplicates(string $phone, ?int $exceptId = null, int $limit = 5): Collection
    {
        $variants = self::phoneVariants($phone);

        if ($variants === []) {
            return new Collection;
        }

        return ServiceRequest::query()
            ->with(['statusModel', 'assignedTo'])
            ->whereIn('phone', $variants)
            ->when($exceptId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Normalizes a lead-form phone typed next to the fixed +966 prefix to the
     * 9 national digits: Arabic-Indic digits → Latin, drops a leading 966 / 0
     * ("٠٥٥ ١٢٣ ٤٥٦٧" → "551234567"). Validate the result with LOCAL_PHONE_PATTERN.
     */
    public static function normalizeLocalPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', self::toLatinDigits($phone)) ?? '';

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        return substr(ltrim($digits, '0'), 0, 9);
    }

    public static function toLatinDigits(string $value): string
    {
        return strtr($value, array_combine(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ));
    }

    /**
     * @throws BlockedPhoneException
     */
    private function guardAgainstBlockedPhone(string $phone): void
    {
        if ($phone === '') {
            return;
        }

        $spamSlug = ServiceRequestStatus::spamBucket()?->slug;

        if ($spamSlug === null) {
            return;
        }

        $isBlocked = ServiceRequest::query()
            ->where('phone', $phone)
            ->where('status', $spamSlug)
            ->exists();

        if ($isBlocked) {
            throw new BlockedPhoneException;
        }
    }

    /**
     * @throws DuplicateServiceRequestException
     */
    private function guardAgainstDuplicateToday(string $phone, ?string $ip): void
    {
        if ($phone === '') {
            return;
        }

        $exists = ServiceRequest::query()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subHours(12))
            // ->where(function ($query) use ($phone, $ip) {
            //     $query->where('phone', $phone)
            //         ->orWhere('ip_address', $ip);
            // })
            ->exists();

        if ($exists) {
            throw new DuplicateServiceRequestException;
        }
    }

    /**
     * @throws TooManyServiceRequestsException
     */
    private function guardAgainstRateLimit(?string $ip, string $phone = '', mixed $name = null, mixed $userAgent = null): void
    {
        if (blank($ip)) {
            return;
        }

        $key = "service_request_submit:{$ip}";

        if (RateLimiter::tooManyAttempts($key, self::MAX_SUBMISSIONS_PER_WINDOW)) {
            Log::channel('lead_spam')->warning('Rate limit exceeded for a service request submission.', [
                'ip_address' => $ip,
                'phone' => $phone,
                'name' => $name,
                'user_agent' => $userAgent,
            ]);

            throw new TooManyServiceRequestsException;
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);
    }

    private function logBotDetection(string $reason, ServiceRequest $request): void
    {
        Log::channel('lead_spam')->warning("Service request flagged as spam ({$reason}).", [
            'service_request_id' => $request->id,
            'ip_address' => $request->ip_address,
            'user_agent' => $request->user_agent,
            'phone' => $request->phone,
            'name' => $request->name,
            'source' => $request->source?->value,
        ]);
    }

    /**
     * A landing-page lead-form section should always be attributed to the
     * landing page, unless the caller explicitly resolved a more specific
     * source (e.g. an admin-configured override, or a complaint/contact layout).
     */
    private function resolveLeadFormSource(ServiceRequestSource $source, ?string $sectionType): ServiceRequestSource
    {
        if ($sectionType === 'landing_lead_form' && $source === ServiceRequestSource::Lead) {
            return ServiceRequestSource::LandingPage;
        }

        return $source;
    }

    private function resolveOwnerTeamId(ServiceRequestSource $source): ?int
    {
        $departmentName = match ($source) {
            ServiceRequestSource::Lead,
            ServiceRequestSource::CorporateTraining,
            ServiceRequestSource::Form,
            ServiceRequestSource::WhatsApp,
            ServiceRequestSource::Phone,
            ServiceRequestSource::Manual,
            ServiceRequestSource::LandingPage => 'Sales',
            ServiceRequestSource::Contact,
            ServiceRequestSource::Complaint => 'Support',
        };

        return Team::query()
            ->whereHas('department', fn ($query) => $query->where('name', $departmentName))
            ->orderBy('id')
            ->value('id');
    }

    private function resolveCourseGroupId(?int $courseId): ?int
    {
        if (! $courseId) {
            return null;
        }

        $course = Course::query()->select(['id'])->with('groups')->find($courseId);
        if (! $course) {
            return null;
        }

        return $course->groups->first()?->id;
    }
}
