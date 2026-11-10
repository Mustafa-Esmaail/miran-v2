<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Comment $comment) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User) {
            $preference = $notifiable->notificationPreference;
            if ($preference?->email_enabled ?? true) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $commenter = $this->comment->user;
        $commentable = $this->comment->commentable;

        $contextName = match (true) {
            $commentable instanceof \App\Models\Course => $commentable->getTranslation('title', 'en'),
            $commentable instanceof \App\Models\Lesson => $commentable->getTranslation('title', 'en'),
            default => null,
        };

        $mail = (new MailMessage)
            ->subject('New comment on your course')
            ->greeting('Hello '.$notifiable->name.',')
            ->line(($commenter?->name ?? 'A student').' left a comment'.($contextName ? ' on "'.$contextName.'"' : '').'.');

        if (filled($this->comment->content)) {
            $mail->line(str($this->comment->content)->limit(200)->toString());
        }

        return $mail;
    }
}
