<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionVote extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionVoteFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'answer_id',
        'user_id',
        'is_helpful',
    ];

    protected function casts(): array
    {
        return [
            'is_helpful' => 'bool',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseQuestion::class, 'question_id');
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(QuestionAnswer::class, 'answer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
