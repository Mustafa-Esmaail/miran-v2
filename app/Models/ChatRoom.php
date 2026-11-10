<?php

namespace App\Models;

use App\Enums\ChatRoomType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'course_id',
        'course_group_id',
        'title',
        'is_active',
        'read_only_at',
        'last_message_at',
        'last_message_by_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChatRoomType::class,
            'is_active' => 'bool',
            'read_only_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastMessageBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatRoomParticipant::class);
    }

    public function isReadOnly(): bool
    {
        return $this->read_only_at !== null;
    }
}
