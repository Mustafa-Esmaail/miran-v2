<?php

namespace App\Repositories\Contracts;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChatMessageRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<ChatMessage>
     */
    public function getForRoom(ChatRoom $room, User $viewer, int $perPage = 30): LengthAwarePaginator;

    public function create(ChatRoom $room, User $sender, string $body, ?int $replyToMessageId = null): ChatMessage;

    public function hide(ChatMessage $message, User $actor, string $reason): ChatMessage;

    public function unhide(ChatMessage $message, User $actor): ChatMessage;
}
