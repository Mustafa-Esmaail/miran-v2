<?php

namespace App\Models;

use App\Enums\QuestionAnswerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'content',
        'status',
        'is_accepted',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuestionAnswerStatus::class,
            'is_accepted' => 'bool',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuestionVote::class, 'answer_id');
    }
}
