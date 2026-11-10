<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\NotificationPreference;
use Illuminate\View\View;
use Livewire\Component;

class NotificationsCenter extends Component
{
    public bool $emailEnabled = true;

    public bool $databaseEnabled = true;

    public bool $announcementsEnabled = true;

    public bool $courseUpdatesEnabled = true;

    public bool $commentRepliesEnabled = true;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $preferences = $user->notificationPreference;
        if (! $preferences) {
            $preferences = NotificationPreference::query()->create([
                'user_id' => $user->id,
                'email_enabled' => true,
                'database_enabled' => true,
                'announcements_enabled' => true,
                'course_updates_enabled' => true,
                'comment_replies_enabled' => true,
            ]);
        }

        $this->emailEnabled = $preferences->email_enabled;
        $this->databaseEnabled = $preferences->database_enabled;
        $this->announcementsEnabled = $preferences->announcements_enabled;
        $this->courseUpdatesEnabled = $preferences->course_updates_enabled;
        $this->commentRepliesEnabled = $preferences->comment_replies_enabled;
    }

    public function savePreferences(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'email_enabled' => $this->emailEnabled,
                'database_enabled' => $this->databaseEnabled,
                'announcements_enabled' => $this->announcementsEnabled,
                'course_updates_enabled' => $this->courseUpdatesEnabled,
                'comment_replies_enabled' => $this->commentRepliesEnabled,
            ]
        );

        session()->flash('status', __('web.notifications.preferences_saved'));
        $this->dispatch('toast', message: __('web.notifications.preferences_saved'));
    }

    public function render(): View
    {
        $rawNotifications = auth()->user()?->notifications()->latest()->get() ?? collect();
        $courseIds = $rawNotifications
            ->map(fn ($notification) => $notification->data['course_id'] ?? null)
            ->filter()
            ->unique()
            ->values();
        $courseSlugs = $courseIds->isNotEmpty()
            ? Course::query()->whereIn('id', $courseIds)->pluck('slug', 'id')->all()
            : [];

        $groupsByCourseId = $courseIds->isNotEmpty()
            ? Enrollment::query()
                ->where('user_id', auth()->id())
                ->whereIn('course_id', $courseIds)
                ->whereNotNull('course_group_id')
                ->pluck('course_group_id', 'course_id')
                ->all()
            : [];

        $notifications = $rawNotifications->map(function ($notification) use ($courseSlugs, $groupsByCourseId) {
            $type = class_basename($notification->type);
            $payload = $this->formatNotificationPayload($type, $notification->data ?? [], $courseSlugs, $groupsByCourseId);

            return [
                'id' => $notification->id,
                'type' => $type,
                'group' => $payload['group'],
                'group_label' => $payload['group_label'],
                'title' => $payload['title'],
                'message' => $payload['message'],
                'action_url' => $payload['action_url'],
                'action_label' => $payload['action_label'],
                'created_at' => $notification->created_at->diffForHumans(),
                'is_unread' => $notification->read_at === null,
            ];
        });

        $grouped = $notifications
            ->groupBy('group')
            ->map(fn ($items) => [
                'label' => $items->first()['group_label'],
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();

        return view('livewire.notifications.notification-center', [
            'groupedNotifications' => $grouped,
        ])
            ->layout('layouts.app');
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  array<int, string>  $courseSlugs
     * @param  array<int, int>  $groupsByCourseId
     * @return array{group:string, group_label:string, title:string, message:string, action_url:?string, action_label:?string}
     */
    private function formatNotificationPayload(string $type, array $data, array $courseSlugs, array $groupsByCourseId = []): array
    {
        $orderNo = $data['order_no'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $courseSlug = $courseId && isset($courseSlugs[$courseId]) ? $courseSlugs[$courseId] : null;
        $groupId = $courseId ? ($groupsByCourseId[$courseId] ?? null) : null;
        $courseTitle = $data['course_title'] ?? null;
        $questionTitle = $data['question_title'] ?? null;
        $status = $data['status'] ?? null;

        $actionUrl = null;
        $actionLabel = null;
        if ($orderNo) {
            $actionUrl = route('dashboard.orders.show', $orderNo);
            $actionLabel = __('web.notifications.actions.view_order');
        } elseif ($courseSlug) {
            $actionUrl = route('courses.show', $courseSlug);
            $actionLabel = __('web.notifications.actions.view_course');
        }

        return match ($type) {
            'PaymentSuccessNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.payment_success'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'PaymentFailedNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.payment_failed'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'BankReceiptSubmittedNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.bank_receipt_submitted'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'BankReceiptApprovedNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.bank_receipt_approved'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'BankReceiptRejectedNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.bank_receipt_rejected'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'EnrollmentActivatedNotification' => $this->payloadForOrder(
                'orders',
                __('web.notifications.titles.enrollment_activated'),
                __('web.notifications.messages.order', ['order' => $orderNo]),
                $actionUrl,
                $actionLabel
            ),
            'EnrollmentConfirmationNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.enrollment_confirmed'),
                $courseTitle
                    ? __('web.notifications.messages.course', ['course' => $courseTitle])
                    : __('web.notifications.messages.generic'),
                $courseSlug && $groupId ? route('dashboard.course-player', [$courseSlug, $groupId]) : $actionUrl,
                __('web.notifications.actions.open_course')
            ),
            'CourseUpdatedNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.course_updated'),
                $courseTitle
                    ? __('web.notifications.messages.course', ['course' => $courseTitle])
                    : __('web.notifications.messages.generic'),
                $actionUrl,
                $actionLabel
            ),
            'CourseStartReminderNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.course_starting'),
                $courseTitle
                    ? __('web.notifications.messages.course', ['course' => $courseTitle])
                    : __('web.notifications.messages.generic'),
                $actionUrl,
                $actionLabel
            ),
            'SessionStartingNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.session_starting'),
                $data['title'] ?? __('web.notifications.messages.generic'),
                null,
                null
            ),
            'RecordingAvailableNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.recording_available'),
                $data['title'] ?? __('web.notifications.messages.generic'),
                null,
                null
            ),
            'QuizResultNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.quiz_result'),
                __('web.notifications.messages.quiz_score', [
                    'title' => $data['title'] ?? __('web.notifications.messages.generic'),
                    'score' => $data['score'] ?? 0,
                ]),
                null,
                null
            ),
            'CertificateIssuedNotification' => $this->payloadForCourse(
                'courses',
                __('web.notifications.titles.certificate_issued'),
                __('web.notifications.messages.certificate', [
                    'course' => $data['course_title'] ?? $data['title'] ?? '',
                ]),
                route('dashboard.downloads'),
                __('web.notifications.actions.view_downloads')
            ),
            'NewCommentNotification' => $this->payloadForCommunity(
                __('web.notifications.titles.new_comment'),
                __('web.notifications.messages.new_comment'),
                null,
                null
            ),
            'NewCommentReplyNotification' => $this->payloadForCommunity(
                __('web.notifications.titles.new_reply'),
                __('web.notifications.messages.new_reply'),
                null,
                null
            ),
            'NewCourseQuestionNotification', 'NewQuestionNotification' => $this->payloadForCommunity(
                __('web.notifications.titles.new_question'),
                $questionTitle
                    ? __('web.notifications.messages.question', ['question' => $questionTitle])
                    : __('web.notifications.messages.generic'),
                $courseSlug ? route('courses.show', $courseSlug).'#questions' : $actionUrl,
                __('web.notifications.actions.view_questions')
            ),
            'NewAnswerNotification', 'NewQuestionAnswerNotification' => $this->payloadForCommunity(
                __('web.notifications.titles.new_answer'),
                $questionTitle
                    ? __('web.notifications.messages.answer', ['question' => $questionTitle])
                    : __('web.notifications.messages.generic'),
                $courseSlug ? route('courses.show', $courseSlug).'#questions' : $actionUrl,
                __('web.notifications.actions.view_questions')
            ),
            'AnnouncementNotification' => $this->payloadForAnnouncement(
                __('web.notifications.titles.announcement'),
                $data['title'] ?? __('web.notifications.messages.generic')
            ),
            default => $this->payloadForSystem(
                __('web.notifications.titles.system'),
                $status ? __('web.notifications.messages.status', ['status' => $status]) : __('web.notifications.messages.generic')
            ),
        };
    }

    private function payloadForOrder(
        string $group,
        string $title,
        string $message,
        ?string $actionUrl,
        ?string $actionLabel
    ): array {
        return [
            'group' => $group,
            'group_label' => __('web.notifications.groups.'.$group),
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
        ];
    }

    private function payloadForCourse(
        string $group,
        string $title,
        string $message,
        ?string $actionUrl,
        ?string $actionLabel
    ): array {
        return [
            'group' => $group,
            'group_label' => __('web.notifications.groups.'.$group),
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
        ];
    }

    private function payloadForCommunity(
        string $title,
        string $message,
        ?string $actionUrl,
        ?string $actionLabel
    ): array {
        return [
            'group' => 'community',
            'group_label' => __('web.notifications.groups.community'),
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
        ];
    }

    private function payloadForAnnouncement(string $title, string $message): array
    {
        return [
            'group' => 'announcements',
            'group_label' => __('web.notifications.groups.announcements'),
            'title' => $title,
            'message' => $message,
            'action_url' => null,
            'action_label' => null,
        ];
    }

    private function payloadForSystem(string $title, string $message): array
    {
        return [
            'group' => 'system',
            'group_label' => __('web.notifications.groups.system'),
            'title' => $title,
            'message' => $message,
            'action_url' => null,
            'action_label' => null,
        ];
    }
}
