<?php

namespace App\Filament\Admin\Resources\ChatRooms\Pages;

use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use App\Services\ChatService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;

class ViewChatRoom extends Page
{
    protected static string $resource = ChatRoomResource::class;

    protected string $view = 'filament.resources.chat-room-resource.pages.view-chat-room';

    public ChatRoom $record;

    public string $body = '';

    public ?int $replyToMessageId = null;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.chat_view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $group = $this->record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '';

        return __('admin.pages.chat_view_title', ['resource' => $group ?: $this->record->type->value]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function sendMessage(): void
    {
        $this->validate(['body' => 'required|string|max:5000']);

        /** @var \App\Models\User $sender */
        $sender = auth('admin')->user();

        try {
            app(ChatService::class)->adminSendMessage(
                $sender,
                $this->record,
                $this->body,
                $this->replyToMessageId,
            );
        } catch (AuthorizationException $e) {
            $this->addError('body', $e->getMessage());

            return;
        }

        $this->body = '';
        $this->replyToMessageId = null;
        $this->dispatch('admin-chat-scroll');
    }

    public function hideMessage(int $messageId, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            return;
        }

        $message = ChatMessage::find($messageId);

        if (! $message || $message->chat_room_id !== $this->record->id) {
            return;
        }

        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        app(ChatService::class)->adminHideMessage($admin, $message, $reason);
        $this->dispatch('admin-chat-scroll');
    }

    public function unhideMessage(int $messageId): void
    {
        $message = ChatMessage::find($messageId);

        if (! $message || $message->chat_room_id !== $this->record->id) {
            return;
        }

        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        app(ChatService::class)->adminUnhideMessage($admin, $message);
        $this->dispatch('admin-chat-scroll');
    }

    public function toggleChatBan(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $user->update(['is_chat_banned' => ! $user->is_chat_banned]);
    }

    public function refreshMessages(): void
    {
        $this->dispatch('admin-chat-scroll');
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
    }

    public function clearReply(): void
    {
        $this->replyToMessageId = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        // Latest 80 messages, reversed to show oldest-first in the chat view.
        $messages = ChatMessage::query()
            ->where('chat_room_id', $this->record->id)
            ->with(['sender', 'replyTo.sender', 'hiddenBy'])
            ->orderByDesc('created_at')
            ->take(80)
            ->get()
            ->reverse()
            ->values();

        $totalMessages = ChatMessage::query()
            ->where('chat_room_id', $this->record->id)
            ->count();

        $participants = $this->record->participants()
            ->with('user')
            ->orderBy('role_in_room')
            ->get();

        $replyToMessage = $this->replyToMessageId
            ? ChatMessage::query()->with('sender')->find($this->replyToMessageId)
            : null;

        return [
            'record' => $this->record,
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'participants' => $participants,
            'replyToMessage' => $replyToMessage,
        ];
    }
}
