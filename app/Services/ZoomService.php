<?php

namespace App\Services;

use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\ZoomLiveAttendance;
use App\Models\ZoomSessionAttendance;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZoomService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function createMeeting(CourseGroupSession $session): array
    {
        $host = $this->resolveHostIdentifier($session);
        $response = $this->zoomClient()
            ->post('https://api.zoom.us/v2/users/'.$host.'/meetings', [
                'topic' => $session->title,
                'type' => 2,
                'start_time' => optional($session->start_at)->toIso8601String(),
                'duration' => (int) $session->duration_minutes,
                'timezone' => config('app.timezone'),
                'agenda' => strip_tags((string) $session->content),
                'settings' => [
                    'join_before_host' => true,
                    'jbh_time' => 10,
                    'approval_type' => 0,
                    'registration_type' => 1,
                    'waiting_room' => false,
                    'host_video' => true,
                    'participant_video' => false,
                    'auto_recording' => 'cloud',
                ],
            ]);

        if (! $response->successful()) {
            Log::channel('zoom')->error('Zoom meeting creation failed for session ID: '.$session->id, [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);
            throw new RuntimeException('Zoom meeting creation failed.');
        }

        $payload = $response->json() ?? [];
        $session->update([
            'zoom_meeting_id' => (string) ($payload['id'] ?? ''),
            'zoom_meeting_uuid' => (string) ($payload['uuid'] ?? ''),
            'zoom_start_url' => (string) ($payload['start_url'] ?? ''),
            'join_url' => (string) ($payload['registration_url'] ?? $payload['join_url'] ?? $session->join_url),
            'zoom_synced_at' => now(),
        ]);

        Log::channel('zoom')->info('Zoom meeting created successfully for session ID: '.$session->id, [
            'meeting_id' => $payload['id'] ?? '',
            'meeting_uuid' => $payload['uuid'] ?? '',
        ]);

        return $payload;
    }

    public function syncRegistrants(CourseGroupSession $session): void
    {
        $session->loadMissing('courseGroup.enrollments.user');

        $enrollments = $session->courseGroup?->enrollments
            ?->filter(fn (Enrollment $e) => $e->user?->email !== null)
            ?? collect();

        $this->registerAndApproveParticipants($session, $enrollments);
    }

    public function registerAndApproveParticipants(CourseGroupSession $session, Collection $enrollments): void
    {
        $meetingId = trim((string) ($session->zoom_meeting_id ?? ''));
        if ($meetingId === '' || $enrollments->isEmpty()) {
            return;
        }

        $registrantIds = [];

        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            if (! $user || trim((string) $user->email) === '') {
                continue;
            }

            $response = $this->zoomClient()
                ->post("https://api.zoom.us/v2/meetings/{$meetingId}/registrants", [
                    'email' => $user->email,
                    'first_name' => $user->name,
                    'last_name' => 'Miran-Stu',
                ]);

            if (! $response->successful()) {
                Log::channel('zoom')->warning('Zoom registrant add failed', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'response' => $response->json(),
                ]);

                continue;
            }

            $registrantId = (string) ($response->json('registrant_id') ?? '');
            if ($registrantId !== '') {
                $registrantIds[] = [
                    'id' => $registrantId,
                    'email' => $user->email,
                ];
            }
        }

        if (empty($registrantIds)) {
            return;
        }

        $approveResponse = $this->zoomClient()
            ->put("https://api.zoom.us/v2/meetings/{$meetingId}/registrants/status", [
                'action' => 'approve',
                'registrants' => $registrantIds,
            ]);

        if (! $approveResponse->successful()) {
            Log::channel('zoom')->error('Zoom registrants bulk approve failed', [
                'session_id' => $session->id,
                'response' => $approveResponse->json(),
            ]);
            throw new RuntimeException('Zoom registrants approval failed.');
        }

        Log::channel('zoom')->info('Zoom registrants registered and approved for session ID: '.$session->id, [
            'count' => count($registrantIds),
        ]);
    }

    public function syncSessionAttendance(CourseGroupSession $session, ?string $meetingUuid = null): void
    {
        $meetingId = trim((string) ($session->zoom_meeting_id ?? ''));
        $fallbackUuid = trim((string) ($meetingUuid ?: $session->zoom_meeting_uuid));

        if ($meetingId === '' && $fallbackUuid === '') {
            Log::channel('zoom')->warning('Zoom attendance sync skipped: no meeting ID or UUID for session ID: '.$session->id);

            return;
        }

        $instanceUuids = $meetingId !== ''
            ? $this->fetchMeetingInstanceUuids($session, $meetingId, $fallbackUuid)
            : ($fallbackUuid !== '' ? [$fallbackUuid] : []);

        if (empty($instanceUuids)) {
            Log::channel('zoom')->warning('Zoom attendance sync skipped: no meeting instances found for session ID: '.$session->id);

            return;
        }

        $participants = [];
        foreach ($instanceUuids as $instanceUuid) {
            $participants = array_merge($participants, $this->fetchInstanceParticipants($session, $instanceUuid));
        }

        $merged = $this->mergeParticipantsByEmail($participants);

        $session->loadMissing('courseGroup.enrollments.user');
        $enrollments = $session->courseGroup?->enrollments?->keyBy(fn ($enrollment) => strtolower((string) $enrollment->user?->email)) ?? collect();

        DB::transaction(function () use ($session, $meetingId, $merged, $enrollments): void {
            ZoomSessionAttendance::query()
                ->where('course_group_session_id', $session->id)
                ->delete();

            foreach ($merged as $entry) {
                $matchedEnrollment = $entry['email'] !== null ? $enrollments->get($entry['email']) : null;

                ZoomSessionAttendance::query()->create([
                    'course_group_session_id' => $session->id,
                    'user_id' => $matchedEnrollment?->user_id,
                    'enrollment_id' => $matchedEnrollment?->id,
                    'zoom_meeting_id' => $meetingId !== '' ? $meetingId : (string) ($session->zoom_meeting_id ?? ''),
                    'participant_name' => $entry['name'],
                    'participant_email' => $entry['email'],
                    'joined_at' => $entry['joined_at'],
                    'left_at' => $entry['left_at'],
                    'duration_minutes' => $entry['duration_minutes'],
                    'raw_payload' => $entry['raw'],
                ]);
            }

            $session->update(['zoom_synced_at' => now()]);
        });

        Log::channel('zoom')->info('Zoom attendance synced successfully for session ID: '.$session->id, [
            'meeting_id' => $meetingId,
            'instances_count' => count($instanceUuids),
            'participants_count' => count($merged),
        ]);
    }

    /**
     * @return list<string>
     */
    private function fetchMeetingInstanceUuids(CourseGroupSession $session, string $meetingId, string $fallbackUuid): array
    {
        $response = $this->zoomClient()->get("https://api.zoom.us/v2/past_meetings/{$meetingId}/instances");

        if (! $response->successful()) {
            Log::channel('zoom')->warning('Zoom meeting instances lookup failed for session ID: '.$session->id, [
                'meeting_id' => $meetingId,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return $fallbackUuid !== '' ? [$fallbackUuid] : [];
        }

        $uuids = collect($response->json('meetings') ?? [])
            ->map(fn ($instance) => trim((string) ($instance['uuid'] ?? '')))
            ->filter(fn (string $uuid) => $uuid !== '')
            ->values()
            ->all();

        if (empty($uuids)) {
            return $fallbackUuid !== '' ? [$fallbackUuid] : [];
        }

        return $uuids;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchInstanceParticipants(CourseGroupSession $session, string $meetingUuid): array
    {
        $participants = [];
        $nextPageToken = null;

        do {
            $response = $this->zoomClient()->get(
                'https://api.zoom.us/v2/past_meetings/'.$this->encodeMeetingUuid($meetingUuid).'/participants',
                array_filter([
                    'page_size' => 300,
                    'next_page_token' => $nextPageToken,
                ]),
            );

            if (! $response->successful()) {
                Log::channel('zoom')->error('Zoom attendance sync failed for session ID: '.$session->id, [
                    'meeting_uuid' => $meetingUuid,
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                throw new RuntimeException('Zoom attendance sync failed.');
            }

            $body = $response->json() ?? [];
            $participants = array_merge($participants, $body['participants'] ?? []);
            $nextPageToken = $body['next_page_token'] ?? null;
        } while (! empty($nextPageToken));

        return $participants;
    }

    /**
     * Merges participant segments across meeting instances into one row per attendee,
     * summing duration and spanning the earliest join / latest leave times.
     *
     * @param  list<array<string, mixed>>  $participants
     * @return list<array{email: ?string, name: string, joined_at: ?Carbon, left_at: ?Carbon, duration_minutes: int, raw: list<array<string, mixed>>}>
     */
    private function mergeParticipantsByEmail(array $participants): array
    {
        $merged = [];

        foreach ($participants as $index => $participant) {
            if (! is_array($participant)) {
                continue;
            }

            $email = strtolower(trim((string) ($participant['user_email'] ?? '')));
            $key = $email !== '' ? $email : 'unmatched:'.$index;

            $joinedAt = $this->parseZoomDate($participant['join_time'] ?? null);
            $leftAt = $this->parseZoomDate($participant['leave_time'] ?? null);
            $duration = (int) ($participant['duration'] ?? 0);

            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'email' => $email !== '' ? $email : null,
                    'name' => (string) ($participant['name'] ?? ''),
                    'joined_at' => $joinedAt,
                    'left_at' => $leftAt,
                    'duration_minutes' => $duration,
                    'raw' => [$participant],
                ];

                continue;
            }

            $merged[$key]['duration_minutes'] += $duration;
            $merged[$key]['raw'][] = $participant;

            if ($joinedAt && (! $merged[$key]['joined_at'] || $joinedAt->lt($merged[$key]['joined_at']))) {
                $merged[$key]['joined_at'] = $joinedAt;
            }

            if ($leftAt && (! $merged[$key]['left_at'] || $leftAt->gt($merged[$key]['left_at']))) {
                $merged[$key]['left_at'] = $leftAt;
            }
        }

        return array_values($merged);
    }

    public function syncRecording(CourseGroupSession $session, array $payload): void
    {
        $object = $payload['object'] ?? [];
        $recordingUrl = (string) ($object['share_url'] ?? '');

        if ($recordingUrl === '' && is_array($object['recording_files'] ?? null)) {
            foreach ($object['recording_files'] as $file) {
                if (! is_array($file)) {
                    continue;
                }

                $recordingUrl = (string) ($file['play_url'] ?? $file['download_url'] ?? '');
                if ($recordingUrl !== '') {
                    break;
                }
            }
        }

        $session->update([
            'zoom_meeting_uuid' => (string) ($object['uuid'] ?? $session->zoom_meeting_uuid),
            'zoom_meeting_id' => (string) ($object['id'] ?? $session->zoom_meeting_id),
            'recording_url' => $recordingUrl !== '' ? $recordingUrl : $session->recording_url,
            'zoom_synced_at' => now(),
        ]);

        Log::channel('zoom')->info('Zoom recording synced for session ID: '.$session->id, [
            'meeting_id' => $object['id'] ?? '',
            'recording_url' => $recordingUrl,
        ]);
    }

    public function handleWebhook(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');
        $object = $payload['payload']['object'] ?? [];
        $meetingId = trim((string) ($object['id'] ?? ''));
        $meetingUuid = trim((string) ($object['uuid'] ?? ''));

        Log::channel('zoom')->info('Zoom webhook received', [
            'event' => $event,
            'meeting_id' => $meetingId,
            'meeting_uuid' => $meetingUuid,
        ]);

        if ($meetingId === '' && $meetingUuid === '') {
            Log::channel('zoom')->warning('Zoom webhook ignored: no meeting ID or UUID');

            return;
        }

        $session = CourseGroupSession::query()
            ->where(function ($query) use ($meetingId, $meetingUuid): void {
                if ($meetingId !== '') {
                    $query->where('zoom_meeting_id', $meetingId);
                }

                if ($meetingUuid !== '') {
                    if ($meetingId !== '') {
                        $query->orWhere('zoom_meeting_uuid', $meetingUuid);
                    } else {
                        $query->where('zoom_meeting_uuid', $meetingUuid);
                    }
                }
            })
            ->latest('id')
            ->first();

        if (! $session) {
            Log::channel('zoom')->warning('Zoom webhook ignored: no matching session found', [
                'meeting_id' => $meetingId,
                'meeting_uuid' => $meetingUuid,
            ]);

            return;
        }

        if ($event === 'recording.completed') {
            $this->syncRecording($session, $payload['payload'] ?? []);
        }

        if (in_array($event, ['meeting.ended', 'recording.completed'], true)) {
            $this->syncSessionAttendance($session, $meetingUuid);
        }

        if ($event === 'meeting.started') {
            $this->handleMeetingStarted($session, $object);
        }

        if ($event === 'meeting.ended') {
            $this->handleMeetingEnded($session, $object);
        }

        if ($event === 'meeting.participant_joined') {
            $this->recordParticipantEvent($session, $object, 'joined');
        }

        if ($event === 'meeting.participant_jbh_joined') {
            $this->recordParticipantEvent($session, $object, 'joined_before_host');
        }

        if ($event === 'meeting.participant_joined_waiting_room') {
            $this->recordParticipantEvent($session, $object, 'waiting_room');
        }

        if ($event === 'meeting.participant_left') {
            $this->handleParticipantLeft($session, $object);
        }

        Log::channel('zoom')->info('Zoom webhook processed successfully for session ID: '.$session->id, [
            'event' => $event,
        ]);
    }

    private function handleMeetingStarted(CourseGroupSession $session, array $object): void
    {
        if ($session->zoom_actual_started_at) {
            return;
        }

        $session->update([
            'zoom_actual_started_at' => $this->parseZoomDate($object['start_time'] ?? null) ?? now(),
        ]);

        Log::channel('zoom')->info('Zoom meeting started for session ID: '.$session->id);
    }

    private function handleMeetingEnded(CourseGroupSession $session, array $object): void
    {
        if ($session->zoom_actual_ended_at) {
            return;
        }

        $session->update([
            'zoom_actual_ended_at' => $this->parseZoomDate($object['end_time'] ?? null) ?? now(),
        ]);

        Log::channel('zoom')->info('Zoom meeting ended for session ID: '.$session->id);
    }

    private function recordParticipantEvent(CourseGroupSession $session, array $object, string $status): void
    {
        $participant = $object['participant'] ?? [];
        if (! is_array($participant)) {
            return;
        }

        $participantId = trim((string) ($participant['id'] ?? $participant['user_id'] ?? ''));
        $email = strtolower(trim((string) ($participant['email'] ?? '')));
        $hostEmail = strtolower(trim((string) ($session->zoom_host_email ?? '')));
        $isHost = $email !== '' && $email === $hostEmail;

        $matchedEnrollment = $this->matchEnrollmentByEmail($session, $email);
        $eventTime = $this->parseZoomDate($participant['join_time'] ?? $participant['date_time'] ?? null) ?? now();

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'user_id' => $matchedEnrollment?->user_id,
            'enrollment_id' => $matchedEnrollment?->id,
            'zoom_participant_id' => $participantId !== '' ? $participantId : null,
            'participant_name' => (string) ($participant['user_name'] ?? ''),
            'participant_email' => $email !== '' ? $email : null,
            'role' => $isHost ? 'host' : 'attendee',
            'status' => $status,
            'joined_at' => $eventTime,
            'left_at' => null,
        ]);

        Log::channel('zoom')->info('Zoom participant event recorded for session ID: '.$session->id, [
            'status' => $status,
            'participant_email' => $email,
            'is_host' => $isHost,
        ]);
    }

    private function handleParticipantLeft(CourseGroupSession $session, array $object): void
    {
        $participant = $object['participant'] ?? [];
        if (! is_array($participant)) {
            return;
        }

        $participantId = trim((string) ($participant['id'] ?? $participant['user_id'] ?? ''));
        $email = strtolower(trim((string) ($participant['email'] ?? '')));

        $query = ZoomLiveAttendance::query()
            ->where('course_group_session_id', $session->id)
            ->stillInMeeting();

        if ($participantId !== '') {
            $query->where('zoom_participant_id', $participantId);
        } else {
            $query->where('participant_email', $email !== '' ? $email : null);
        }

        $openRecord = $query->latest('joined_at')->first();
        $openRecord?->update([
            'left_at' => $this->parseZoomDate($participant['leave_time'] ?? null) ?? now(),
        ]);

        Log::channel('zoom')->info('Zoom participant left for session ID: '.$session->id, [
            'participant_email' => $email,
        ]);
    }

    private function matchEnrollmentByEmail(CourseGroupSession $session, string $email): ?Enrollment
    {
        if ($email === '') {
            return null;
        }

        $session->loadMissing('courseGroup.enrollments.user');

        return $session->courseGroup?->enrollments
            ?->first(fn (Enrollment $enrollment) => strtolower((string) $enrollment->user?->email) === $email);
    }

    public function validateEndpoint(string $plainToken): string
    {
        $secret = (string) ($this->settings->zoomConfig()['webhook_secret'] ?? '');

        return hash_hmac('sha256', $plainToken, $secret);
    }

    private function zoomClient(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        $config = $this->settings->zoomConfig();
        if (! $this->settings->zoomIsReady()) {
            throw new RuntimeException('Zoom credentials are not configured.');
        }

        $response = Http::asForm()
            ->withBasicAuth((string) $config['client_id'], (string) $config['client_secret'])
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => (string) $config['account_id'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zoom access token request failed.');
        }

        $token = (string) ($response->json('access_token') ?? '');
        if ($token === '') {
            throw new RuntimeException('Zoom access token missing.');
        }

        return $token;
    }

    private function resolveHostIdentifier(CourseGroupSession $session): string
    {
        $host = trim((string) ($session->zoom_host_email ?: $session->zoomHostUser?->email ?: 'me'));

        return $host !== '' ? $host : 'me';
    }

    private function encodeMeetingUuid(string $uuid): string
    {
        return rawurlencode(rawurlencode($uuid));
    }

    private function parseZoomDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->setTimezone(config('app.timezone'));
    }
}
