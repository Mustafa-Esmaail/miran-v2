<?php

namespace App\Repositories\Contracts;

use App\Enums\ChatRoomType;
use App\Models\ChatRoom;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Support\Collection;

interface ChatRoomRepositoryInterface
{
    public function findGroupRoom(CourseGroup $group): ?ChatRoom;

    public function findDirectRoom(CourseGroup $group, int $userAId, int $userBId): ?ChatRoom;

    /**
     * @return Collection<int, ChatRoom>
     */
    public function getAccessibleRoomsForUser(User $user): Collection;

    public function create(ChatRoomType $type, CourseGroup $group, User $createdBy, ?string $title = null): ChatRoom;

    public function touchLastMessage(ChatRoom $room, User $sender): void;
}
