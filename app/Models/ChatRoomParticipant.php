<?php

namespace App\Models;

use App\Enums\RoleInRoom;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRoomParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role_in_room',
        'last_read_message_id',
        'joined_at',
        'left_at',
        'muted_at',
    ];

    protected function casts(): array
    {
        return [
            'role_in_room' => RoleInRoom::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'muted_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }
}
