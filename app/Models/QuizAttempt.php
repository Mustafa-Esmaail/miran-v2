<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'int',
            'answers' => 'array',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAttemptNumber(): int
    {
        return static::query()
            ->where('quiz_id', $this->quiz_id)
            ->where('user_id', $this->user_id)
            ->where('id', '<=', $this->id)
            ->count();
    }

    public function isPassed(): bool
    {
        $this->loadMissing('quiz.questions');
        $totalPoints = (int) ($this->quiz?->questions->sum('points') ?? 0);
        $passingScore = (int) ($this->quiz?->passing_score ?? 0);

        if ($totalPoints === 0) {
            return $passingScore === 0;
        }

        return (int) round(($this->score / $totalPoints) * 100) >= $passingScore;
    }

    public function totalPoints(): int
    {
        $this->loadMissing('quiz.questions');

        return (int) ($this->quiz?->questions->sum('points') ?? 0);
    }
}
