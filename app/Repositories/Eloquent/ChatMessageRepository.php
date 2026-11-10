<?php

namespace App\Repositories\Eloquent;

use App\Enums\ChatMessageActionType;
use App\Models\ChatMessage;
use App\Models\ChatMessageAction;
use App\Models\ChatRoom;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatMessageRepository implements ChatMessageRepositoryInterface
{
    public function getForRoom(ChatRoom $room, User $viewer, int $perPage = 30): LengthAwarePaginator
    {
        $query = ChatMessage::query()
            ->where('chat_room_id', $room->id)
            ->with(['sender', 'replyTo.sender'])
            ->orderBy('created_at');

        // Instructors see hidden messages; students do not.
        $isTrainer = $room->course->trainers()->where('users.id', $viewer->id)->exists();
        if (! $isTrainer) {
            $query->whereNull('hidden_at');
        }

        return $query->paginate($perPage);
    }

    public function create(ChatRoom $room, User $sender, string $body, ?int $replyToMessageId = null): ChatMessage
    {
        return ChatMessage::query()->create([
            'chat_room_id' => $room->id,
            'sender_user_id' => $sender->id,
            'body' => $body,
            'reply_to_message_id' => $replyToMessageId,
        ]);
    }

    public function hide(ChatMessage $message, User $actor, string $reason): ChatMessage
    {
        $message->update([
            'hidden_at' => now(),
            'hidden_by_user_id' => $actor->id,
            'hidden_reason' => $reason,
        ]);

        ChatMessageAction::query()->create([
            'chat_message_id' => $message->id,
            'actor_user_id' => $actor->id,
            'action' => ChatMessageActionType::Hidden,
            'note' => $reason,
            'created_at' => now(),
        ]);

        return $message->refresh();
    }

    public function unhide(ChatMessage $message, User $actor): ChatMessage
    {
        $message->update([
            'hidden_at' => null,
            'hidden_by_user_id' => null,
            'hidden_reason' => null,
        ]);

        ChatMessageAction::query()->create([
            'chat_message_id' => $message->id,
            'actor_user_id' => $actor->id,
            'action' => ChatMessageActionType::Unhidden,
            'created_at' => now(),
        ]);

        return $message->refresh();
    }
}
