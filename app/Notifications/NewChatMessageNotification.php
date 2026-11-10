<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification
{
    public function __construct(private readonly ChatMessage $message) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $preference = $notifiable->notificationPreference;
        $databaseEnabled = $preference?->database_enabled ?? true;

        return $databaseEnabled ? ['database'] : [];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'chat_message_id' => $this->message->id,
            'chat_room_id' => $this->message->chat_room_id,
            'sender_name' => $this->message->sender?->name,
            'body' => $this->message->body,
        ];
    }
}
