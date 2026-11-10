<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\ChatMessage;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\CourseQuestion;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\QuestionAnswer;
use App\Models\Quiz;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Notifications\BankReceiptApprovedNotification;
use App\Notifications\BankReceiptRejectedNotification;
use App\Notifications\BankReceiptSubmittedNotification;
use App\Notifications\CertificateIssuedNotification;
use App\Notifications\CourseStartReminderNotification;
use App\Notifications\CourseUpdatedNotification;
use App\Notifications\EnrollmentActivatedNotification;
use App\Notifications\EnrollmentConfirmationNotification;
use App\Notifications\NewAnswerNotification;
use App\Notifications\NewChatMessageNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewCommentReplyNotification;
use App\Notifications\NewCourseGroupContentNotification;
use App\Notifications\NewQuestionNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSuccessNotification;
use App\Notifications\QuizResultNotification;
use App\Notifications\RecordingAvailableNotification;
use App\Notifications\SessionStartingNotification;
use App\Notifications\StudentLessonViewedNotification;
use App\Notifications\StudentQuizSubmittedNotification;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotificationService
{
    public function notifyPaymentSuccess(Order $order): void
    {
        $notification = new PaymentSuccessNotification($order);
        $this->sendNotification($order->user, $notification);
        $this->notifyAdminsForFrontendAction($notification, collect([$order->user]));
    }

    public function notifyPaymentFailed(Order $order): void
    {
        $notification = new PaymentFailedNotification($order);
        $this->sendNotification($order->user, $notification);
        $this->notifyAdminsForFrontendAction($notification, collect([$order->user]));
    }

    public function notifyBankReceiptSubmitted(Order $order): void
    {
        $notification = new BankReceiptSubmittedNotification($order);
        $this->sendNotification($order->user, $notification);
        $this->notifyAdminsForFrontendAction($notification, collect([$order->user]));
    }

    public function notifyBankReceiptApproved(Order $order): void
    {
        $receiptNotification = new BankReceiptApprovedNotification($order);
        $this->sendNotification($order->user, $receiptNotification);
        $this->notifyAdminsForFrontendAction($receiptNotification, collect([$order->user]));

        $enrollmentNotification = new EnrollmentActivatedNotification($order);
        $this->sendNotification($order->user, $enrollmentNotification);
        $this->notifyAdminsForFrontendAction($enrollmentNotification, collect([$order->user]));
    }

    public function notifyBankReceiptRejected(Order $order): void
    {
        $notification = new BankReceiptRejectedNotification($order);
        $this->sendNotification($order->user, $notification);
        $this->notifyAdminsForFrontendAction($notification, collect([$order->user]));
    }

    public function notifySessionStartingSoon($user, string $title, string $startAt): void
    {
        $notification = new SessionStartingNotification($title, $startAt);
        $this->sendNotification($user, $notification);
    }

    public function notifyRecordingAvailable($user, string $title): void
    {
        $notification = new RecordingAvailableNotification($title);
        $this->sendNotification($user, $notification);
    }

    public function notifyQuizResult($user, string $title, int $score): void
    {
        $notification = new QuizResultNotification($title, $score);
        $this->sendNotification($user, $notification);
    }

    public function notifyCertificateIssued($user, string $courseTitle): void
    {
        $notification = new CertificateIssuedNotification($courseTitle);
        $this->sendNotification($user, $notification);
    }

    public function notifyNewCourseQuestion(Course $course, CourseQuestion $question): void
    {
        $recipients = $course->trainers()->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new NewQuestionNotification($course, $question);
        $this->sendNotification($recipients, $notification);
    }

    public function notifyNewQuestionAnswer(CourseQuestion $question, QuestionAnswer $answer): void
    {
        $recipient = $question->user;

        if (! $recipient) {
            return;
        }

        if ($recipient->id === $answer->user_id) {
            return;
        }

        $notification = new NewAnswerNotification($question, $answer);
        $this->sendNotification($recipient, $notification);
    }

    public function notifyNewComment(Comment $comment): void
    {
        $commentable = $comment->commentable;

        if ($commentable instanceof \App\Models\Course) {
            $recipients = $commentable->trainers()->get();
        } elseif ($commentable instanceof \App\Models\Lesson) {
            $recipients = $commentable->course?->trainers()->get() ?? collect();
        } else {
            $recipients = collect();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new NewCommentNotification($comment);
        $this->sendNotification($recipients, $notification);
    }

    public function notifyNewCommentReply(Comment $comment, CommentReply $reply): void
    {
        $recipient = $reply->parentReply?->user ?? $comment->user;

        if (! $recipient) {
            return;
        }

        if ($recipient->id === $reply->user_id) {
            return;
        }

        $preference = $recipient->notificationPreference;
        if ($preference && ! $preference->comment_replies_enabled) {
            return;
        }

        $notification = new NewCommentReplyNotification($comment, $reply);
        $this->sendNotification($recipient, $notification);
    }

    public function notifyCourseUpdated(Course $course, array $changes = []): void
    {
        $recipients = User::query()
            ->with('notificationPreference')
            ->whereHas('enrollments', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->get();

        $recipients = $this->filterByPreference($recipients, 'course_updates_enabled');

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new CourseUpdatedNotification($course, $changes);
        $this->sendNotification($recipients, $notification);
    }

    public function notifyCourseStartReminder(CourseGroup $group): void
    {
        $recipients = User::query()
            ->with('notificationPreference')
            ->whereHas('enrollments', function ($query) use ($group) {
                $query->where('course_group_id', $group->id);
            })
            ->get();

        $recipients = $this->filterByPreference($recipients, 'course_updates_enabled');

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new CourseStartReminderNotification($group);
        $this->sendNotification($recipients, $notification);
    }

    public function notifyEnrollmentConfirmed(Enrollment $enrollment): void
    {
        $group = $enrollment->courseGroup;
        if (! $group) {
            return;
        }

        $user = $enrollment->user;
        if (! $user) {
            return;
        }

        $notification = new EnrollmentConfirmationNotification($group);
        $this->sendNotification($user, $notification);
    }

    public function notifyAnnouncement(Announcement $announcement): void
    {
        $recipients = User::query()->with('notificationPreference')->get();

        $recipients = $this->filterByPreference($recipients, 'announcements_enabled');

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new AnnouncementNotification($announcement);
        $this->sendNotification($recipients, $notification);
    }

    private function filterByPreference(Collection $users, string $field): Collection
    {
        return $users->filter(function (User $user) use ($field) {
            $preference = $user->notificationPreference;

            return $preference ? (bool) $preference->{$field} : true;
        });
    }

    public function notifyNewChatMessage(ChatMessage $message, User $recipient): void
    {
        $notification = new NewChatMessageNotification($message);
        $this->notifyUser($recipient, $notification);
        $this->notifyAdminsForFrontendAction(
            $notification,
            collect([$recipient, $message->sender])->filter()
        );
    }

    public function notifyNewCourseGroupContent(CourseGroupSession $session): void
    {
        $group = $session->courseGroup;

        if (! $group) {
            return;
        }

        $recipients = User::query()
            ->with('notificationPreference')
            ->whereHas('enrollments', function ($query) use ($group): void {
                $query
                    ->where('course_group_id', $group->id)
                    ->where('status', \App\Enums\EnrollmentStatus::Active);
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->sendNotification($recipients, new NewCourseGroupContentNotification($session));
    }

    public function notifyInstructorsQuizSubmitted(Enrollment $enrollment, Quiz $quiz, int $score, int $totalPoints): void
    {
        try {
            $student = auth()->user();

            if (! $student) {
                return;
            }

            $instructors = $enrollment->courseGroup->instructorsQuery()->get();

            if ($instructors->isEmpty()) {
                return;
            }

            $courseTitle = $enrollment->course->getTranslation('title', 'en');
            $notification = new StudentQuizSubmittedNotification($student, $quiz, $courseTitle, $score, $totalPoints);
            $this->sendNotification($instructors, $notification);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    public function notifyInstructorsLessonViewed(Enrollment $enrollment, Lesson $lesson): void
    {
        try {
            $student = auth()->user();

            if (! $student) {
                return;
            }

            $instructors = $enrollment->courseGroup->instructorsQuery()->get();

            if ($instructors->isEmpty()) {
                return;
            }

            $courseTitle = $enrollment->course->getTranslation('title', 'en');
            $notification = new StudentLessonViewedNotification($student, $lesson, $courseTitle);
            $this->sendNotification($instructors, $notification);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    public function notifyServiceRequestCreated(ServiceRequest $request): void
    {
        $admins = $this->getAdminUsers();
        if ($admins->isEmpty()) {
            return;
        }

        try {
            FilamentNotification::make()
                ->title(__('admin.notifications.service_request_created'))
                ->body(__('admin.notifications.service_request_created_body', [
                    'name' => $request->name,
                    'email' => $request->email,
                ]))
                ->icon('heroicon-o-ticket')
                ->warning()
                ->actions([
                    FilamentAction::make('view')
                        ->label(__('admin.notifications.view'))
                        ->url(route('filament.admin.resources.service-requests.edit', $request->id)),
                ])
                ->sendToDatabase($admins);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    public function notifyOrderPlaced(Order $order): void
    {
        $admins = $this->getAdminUsers();
        if ($admins->isEmpty()) {
            return;
        }

        $order->loadMissing('user');

        try {
            FilamentNotification::make()
                ->title(__('admin.notifications.order_placed'))
                ->body(__('admin.notifications.order_placed_body', [
                    'order_no' => $order->order_no,
                    'name' => $order->user?->name ?? '—',
                ]))
                ->icon('heroicon-o-shopping-cart')
                ->info()
                ->actions([
                    FilamentAction::make('view')
                        ->label(__('admin.notifications.view'))
                        ->url(route('filament.admin.resources.orders.edit', $order->id)),
                ])
                ->sendToDatabase($admins);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    private function getAdminUsers(): Collection
    {
        return User::query()
            ->whereHas('roles', function ($query): void {
                $query
                    ->whereIn('name', ['admin', 'super-admin'])
                    ->whereIn('guard_name', ['admin', 'web']);
            })
            ->get();
    }

    private function notifyAdminsForFrontendAction(BaseNotification $notification, ?Collection $exclude = null): void
    {
        $admins = $this->getAdminUsers();

        if ($exclude) {
            $excludedIds = $exclude->pluck('id')->filter();
            if ($excludedIds->isNotEmpty()) {
                $admins = $admins->reject(fn (User $user) => $excludedIds->contains($user->id));
            }
        }

        if ($admins->isEmpty()) {
            return;
        }

        $this->sendNotification($admins, $notification);
    }

    /**
     * @param  mixed  $notifiables
     */
    private function sendNotification($notifiables, BaseNotification $notification): void
    {
        try {
            Notification::send($notifiables, $notification);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    private function notifyUser(User $user, BaseNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            $this->reportException($exception);
        }
    }

    private function reportException(Throwable $exception): void
    {
        try {
            report($exception);
        } catch (Throwable) {
            // swallow logging/reporting failures to avoid breaking user flows
        }
    }
}
