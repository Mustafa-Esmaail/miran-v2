<?php

namespace App\Repositories\Eloquent;

use App\Enums\ChatRoomType;
use App\Models\ChatRoom;
use App\Models\CourseGroup;
use App\Models\User;
use App\Repositories\Contracts\ChatRoomRepositoryInterface;
use Illuminate\Support\Collection;

class ChatRoomRepository implements ChatRoomRepositoryInterface
{
    public function findGroupRoom(CourseGroup $group): ?ChatRoom
    {
        return ChatRoom::query()
            ->where('course_group_id', $group->id)
            ->where('type', ChatRoomType::CourseGroup)
            ->first();
    }

    public function findDirectRoom(CourseGroup $group, int $userAId, int $userBId): ?ChatRoom
    {
        $sorted = collect([$userAId, $userBId])->sort()->values();

        return ChatRoom::query()
            ->where('course_group_id', $group->id)
            ->where('type', ChatRoomType::Direct)
            ->whereHas('participants', function ($q) use ($sorted) {
                $q->where('user_id', $sorted[0]);
            })
            ->whereHas('participants', function ($q) use ($sorted) {
                $q->where('user_id', $sorted[1]);
            })
            ->first();
    }

    public function getAccessibleRoomsForUser(User $user): Collection
    {
        return ChatRoom::query()
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNull('left_at');
            })
            ->with(['courseGroup', 'course', 'participants.user'])
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function create(ChatRoomType $type, CourseGroup $group, User $createdBy, ?string $title = null): ChatRoom
    {
        return ChatRoom::query()->create([
            'type' => $type,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'title' => $title,
            'is_active' => true,
            'created_by_user_id' => $createdBy->id,
        ]);
    }

    public function touchLastMessage(ChatRoom $room, User $sender): void
    {
        $room->update([
            'last_message_at' => now(),
            'last_message_by_user_id' => $sender->id,
        ]);
    }
}
