<?php

namespace App\Models;

use App\Enums\ChatMessageActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_message_id',
        'actor_user_id',
        'action',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ChatMessageActionType::class,
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
