<?php

namespace App\Services;

use App\Enums\ChatRoomType;
use App\Enums\EnrollmentStatus;
use App\Enums\RoleInRoom;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomParticipant;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatRoomRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChatService
{
    public function __construct(
        private readonly ChatRoomRepositoryInterface $rooms,
        private readonly ChatMessageRepositoryInterface $messages,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return Collection<int, ChatRoom>
     */
    public function getRoomsForUser(User $user): Collection
    {
        return $this->rooms->getAccessibleRoomsForUser($user);
    }

    public function getUnreadCountForUser(User $user): int
    {
        return ChatRoomParticipant::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('chat_messages')
                    ->whereColumn('chat_messages.chat_room_id', 'chat_room_participants.chat_room_id')
                    ->whereNull('chat_messages.hidden_at')
                    ->where(function ($query) {
                        $query
                            ->whereColumn('chat_messages.id', '>', 'chat_room_participants.last_read_message_id')
                            ->orWhereNull('chat_room_participants.last_read_message_id');
                    });
            })
            ->count();
    }

    public function findOrCreateGroupRoom(CourseGroup $group, User $actor): ChatRoom
    {
        $existing = $this->rooms->findGroupRoom($group);
        if ($existing) {
            return $existing;
        }

        $room = $this->rooms->create(
            ChatRoomType::CourseGroup,
            $group,
            $actor,
            $group->getTranslation('title', app()->getLocale()),
        );

        $this->seedGroupRoomParticipants($room, $group);

        return $room;
    }

    public function findOrCreateDirectRoom(User $initiator, User $recipient, CourseGroup $group): ChatRoom
    {
        $this->authorizeDirectRoom($initiator, $recipient, $group);

        $existing = $this->rooms->findDirectRoom($group, $initiator->id, $recipient->id);
        if ($existing) {
            return $existing;
        }

        $room = $this->rooms->create(ChatRoomType::Direct, $group, $initiator);

        $initiatorRole = $this->resolveRole($initiator, $group);
        $recipientRole = $this->resolveRole($recipient, $group);

        $this->addParticipant($room, $initiator, $initiatorRole);
        $this->addParticipant($room, $recipient, $recipientRole);

        return $room;
    }

    /**
     * @return LengthAwarePaginator<ChatMessage>
     */
    public function getMessages(User $viewer, ChatRoom $room, int $perPage = 30): LengthAwarePaginator
    {
        $this->authorizeRoomAccess($viewer, $room);

        return $this->messages->getForRoom($room, $viewer, $perPage);
    }

    /**
     * Latest $limit messages ordered oldest-first (for display).
     *
     * @return \Illuminate\Support\Collection<int, ChatMessage>
     */
    public function getLatestMessages(User $viewer, ChatRoom $room, int $limit = 40): Collection
    {
        $this->authorizeRoomAccess($viewer, $room);

        $isTrainer = $room->courseGroup?->instructorsQuery()->where('users.id', $viewer->id)->exists();

        $query = ChatMessage::query()
            ->where('chat_room_id', $room->id)
            ->with(['sender', 'replyTo.sender'])
            ->orderByDesc('created_at')
            ->take($limit);

        if (! $isTrainer) {
            $query->whereNull('hidden_at');
        }

        return $query->get()->reverse()->values();
    }

    public function sendMessage(User $sender, ChatRoom $room, string $body, ?int $replyToMessageId = null): ChatMessage
    {
        $this->authorizeRoomAccess($sender, $room);

        if ($sender->isChatBanned()) {
            throw new AuthorizationException(__('web.chat.banned_from_chat'));
        }

        if ($room->isReadOnly()) {
            throw new AuthorizationException('This room is read-only.');
        }

        $message = $this->messages->create($room, $sender, $body, $replyToMessageId);
        $this->rooms->touchLastMessage($room, $sender);

        try {
            $this->notifyParticipants($room, $message, $sender);
        } catch (\Throwable) {
            // notification failure must not block message delivery
        }

        return $message;
    }

    /**
     * Hide a message from the admin panel, bypassing the instructor-only check.
     */
    public function adminHideMessage(User $admin, ChatMessage $message, string $reason): ChatMessage
    {
        return $this->messages->hide($message, $admin, $reason);
    }

    /**
     * Unhide a message from the admin panel, bypassing the instructor-only check.
     */
    public function adminUnhideMessage(User $admin, ChatMessage $message): ChatMessage
    {
        return $this->messages->unhide($message, $admin);
    }

    /**
     * Send a message from an admin/instructor via Filament, bypassing the
     * participant check. Admins are allowed to moderate any room.
     */
    public function adminSendMessage(User $sender, ChatRoom $room, string $body, ?int $replyToMessageId = null): ChatMessage
    {
        if ($room->isReadOnly()) {
            throw new AuthorizationException('This room is read-only.');
        }

        $message = $this->messages->create($room, $sender, $body, $replyToMessageId);
        $this->rooms->touchLastMessage($room, $sender);

        try {
            $this->notifyParticipants($room, $message, $sender);
        } catch (\Throwable) {
            // notification failure must not block message delivery
        }

        return $message;
    }

    public function markRoomRead(User $user, ChatRoom $room): void
    {
        $lastMessageId = ChatMessage::query()
            ->where('chat_room_id', $room->id)
            ->max('id');

        if ($lastMessageId === null) {
            return;
        }

        ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($lastMessageId) {
                $query
                    ->whereNull('last_read_message_id')
                    ->orWhere('last_read_message_id', '<', $lastMessageId);
            })
            ->update(['last_read_message_id' => $lastMessageId]);
    }

    public function hideMessage(User $actor, ChatMessage $message, string $reason): ChatMessage
    {
        $room = $message->room;
        $this->authorizeHide($actor, $room);

        return $this->messages->hide($message, $actor, $reason);
    }

    public function unhideMessage(User $actor, ChatMessage $message): ChatMessage
    {
        $room = $message->room;
        $this->authorizeHide($actor, $room);

        return $this->messages->unhide($message, $actor);
    }

    public function addEnrolledStudentToGroupRoom(Enrollment $enrollment): void
    {
        $group = $enrollment->courseGroup;
        $student = $enrollment->user;

        if (! $group || ! $student) {
            return;
        }

        $room = $this->findOrCreateGroupRoom($group, $this->resolveSystemSender($group, $student));

        $existing = ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $enrollment->user_id)
            ->first();

        if ($existing) {
            $existing->update(['left_at' => null, 'joined_at' => now()]);
        } else {
            $this->addParticipant($room, $student, RoleInRoom::Student);
        }

        $this->ensureDirectRoomsForEnrollment($enrollment);
    }

    public function removeEnrolledStudentFromGroupRoom(Enrollment $enrollment): void
    {
        $group = $enrollment->courseGroup;
        if (! $group) {
            return;
        }

        $room = $this->rooms->findGroupRoom($group);
        if (! $room) {
            return;
        }

        ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $enrollment->user_id)
            ->update(['left_at' => now()]);
    }

    public function archiveGroupRoom(CourseGroup $group): void
    {
        ChatRoom::query()
            ->where('course_group_id', $group->id)
            ->where('type', ChatRoomType::CourseGroup)
            ->update(['read_only_at' => now(), 'is_active' => false]);
    }

    private function seedGroupRoomParticipants(ChatRoom $room, CourseGroup $group): void
    {
        $enrollments = Enrollment::query()
            ->where('course_group_id', $group->id)
            ->where('status', EnrollmentStatus::Active)
            ->with('user')
            ->get();

        foreach ($enrollments as $enrollment) {
            $this->addParticipant($room, $enrollment->user, RoleInRoom::Student);
        }

        $trainers = $group->instructorsQuery()->get();
        foreach ($trainers as $trainer) {
            $this->addParticipant($room, $trainer, RoleInRoom::Instructor);
        }
    }

    private function addParticipant(ChatRoom $room, User $user, RoleInRoom $role): void
    {
        ChatRoomParticipant::query()->updateOrCreate(
            ['chat_room_id' => $room->id, 'user_id' => $user->id],
            ['role_in_room' => $role, 'joined_at' => now(), 'left_at' => null],
        );
    }

    private function authorizeRoomAccess(User $user, ChatRoom $room): void
    {
        $isParticipant = ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if (! $isParticipant) {
            throw new AuthorizationException('You do not have access to this room.');
        }
    }

    private function authorizeDirectRoom(User $initiator, User $recipient, CourseGroup $group): void
    {
        $initiatorIsTrainer = $group->instructorsQuery()->where('users.id', $initiator->id)->exists();
        $recipientIsTrainer = $group->instructorsQuery()->where('users.id', $recipient->id)->exists();

        $initiatorIsStudent = Enrollment::query()
            ->where('user_id', $initiator->id)
            ->where('course_group_id', $group->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        $recipientIsStudent = Enrollment::query()
            ->where('user_id', $recipient->id)
            ->where('course_group_id', $group->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        $initiatorInGroup = $initiatorIsTrainer || $initiatorIsStudent;
        $recipientInGroup = $recipientIsTrainer || $recipientIsStudent;

        if (! $initiatorInGroup || ! $recipientInGroup) {
            throw new AuthorizationException('Both users must be members of the same course group.');
        }
    }

    private function authorizeHide(User $actor, ChatRoom $room): void
    {
        $isTrainer = $room->courseGroup->instructorsQuery()->where('users.id', $actor->id)->exists();

        if (! $isTrainer) {
            throw new AuthorizationException('Only instructors can hide or unhide messages.');
        }
    }

    private function resolveRole(User $user, CourseGroup $group): RoleInRoom
    {
        $isTrainer = $group->instructorsQuery()->where('users.id', $user->id)->exists();

        return $isTrainer ? RoleInRoom::Instructor : RoleInRoom::Student;
    }

    private function notifyParticipants(ChatRoom $room, ChatMessage $message, User $sender): void
    {
        $participants = ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->whereNull('left_at')
            ->where('user_id', '!=', $sender->id)
            ->whereNull('muted_at')
            ->with('user')
            ->get();

        foreach ($participants as $participant) {
            $this->notifications->notifyNewChatMessage($message, $participant->user);
        }
    }

    private function ensureDirectRoomsForEnrollment(Enrollment $enrollment): void
    {
        $group = $enrollment->courseGroup;
        $student = $enrollment->user;

        if (! $group || ! $student) {
            return;
        }

        foreach ($group->instructorsQuery()->get() as $trainer) {
            $this->findOrCreateDirectRoom($student, $trainer, $group);
        }
    }

    private function seedDirectWelcomeMessage(ChatRoom $room, CourseGroup $group, User $initiator, User $recipient): void
    {
        if (ChatMessage::query()->where('chat_room_id', $room->id)->exists()) {
            return;
        }

        $sender = $this->resolveSystemSender($group, $recipient);
        $student = $this->resolveRole($initiator, $group) === RoleInRoom::Student ? $initiator : $recipient;
        $trainer = $this->resolveRole($initiator, $group) === RoleInRoom::Instructor ? $initiator : $recipient;

        $this->createSystemMessage(
            $room,
            $sender,
            __('web.chat.welcome_direct', [
                'student' => $student->name,
                'trainer' => $trainer->name,
            ]),
        );
    }

    private function seedGroupWelcomeMessage(ChatRoom $room, CourseGroup $group, User $student): void
    {
        $sender = $this->resolveSystemSender($group, $student);

        $this->createSystemMessage(
            $room,
            $sender,
            __('web.chat.welcome_group', [
                'student' => $student->name,
                'group' => $room->title ?? $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
            ]),
        );
    }

    private function createSystemMessage(ChatRoom $room, User $sender, string $body): ChatMessage
    {
        $message = $this->messages->create($room, $sender, $body);
        $this->rooms->touchLastMessage($room, $sender);

        return $message;
    }

    private function resolveSystemSender(CourseGroup $group, User $fallback): User
    {
        $admin = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin']);
            })
            ->first();

        if ($admin) {
            return $admin;
        }

        $trainer = $group->instructorsQuery()->first();

        return $trainer ?? $fallback;
    }
}
