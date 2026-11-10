<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\CommentReply;
use Illuminate\Notifications\Notification;

class NewCommentReplyNotification extends Notification
{
    public function __construct(
        private readonly Comment $comment,
        private readonly CommentReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'reply_id' => $this->reply->id,
        ];
    }
}
