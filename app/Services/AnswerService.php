<?php

namespace App\Services;

use App\Enums\QuestionAnswerStatus;
use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use App\Models\User;
use App\Repositories\Contracts\QuestionAnswerRepositoryInterface;
use App\Repositories\Contracts\QuestionVoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AnswerService
{
    public function __construct(
        private readonly QuestionAnswerRepositoryInterface $answers,
        private readonly QuestionVoteRepositoryInterface $votes,
        private readonly NotificationService $notifications,
        private readonly QuestionService $questions
    ) {}

    public function createAnswer(CourseQuestion $question, User $user, string $content): QuestionAnswer
    {
        $answer = $this->answers->create([
            'question_id' => $question->id,
            'user_id' => $user->id,
            'content' => $content,
            'status' => QuestionAnswerStatus::Pending,
            'is_accepted' => false,
        ]);

        $this->notifications->notifyNewQuestionAnswer($question, $answer);

        return $answer;
    }

    public function approve(QuestionAnswer $answer): QuestionAnswer
    {
        $answer->forceFill(['status' => QuestionAnswerStatus::Approved])->save();

        return $answer;
    }

    public function reject(QuestionAnswer $answer): QuestionAnswer
    {
        $answer->forceFill(['status' => QuestionAnswerStatus::Rejected])->save();

        return $answer;
    }

    public function approveMany(array $ids): void
    {
        DB::table('question_answers')->whereIn('id', $ids)->update([
            'status' => QuestionAnswerStatus::Approved->value,
            'updated_at' => now(),
        ]);
    }

    public function rejectMany(array $ids): void
    {
        DB::table('question_answers')->whereIn('id', $ids)->update([
            'status' => QuestionAnswerStatus::Rejected->value,
            'updated_at' => now(),
        ]);
    }

    public function acceptAnswer(QuestionAnswer $answer, User $actor): QuestionAnswer
    {
        $question = $answer->question;

        if (! $question) {
            throw new \RuntimeException(__('web.questions.invalid_question'));
        }

        if ($question->user_id !== $actor->id && ! $this->questions->isInstructor($question->course, $actor)) {
            throw new \RuntimeException(__('web.questions.answer_accept_denied'));
        }

        DB::transaction(function () use ($answer): void {
            QuestionAnswer::query()
                ->where('question_id', $answer->question_id)
                ->update(['is_accepted' => false]);

            $answer->forceFill(['is_accepted' => true])->save();
        });

        return $answer;
    }

    public function toggleHelpful(QuestionAnswer $answer, User $user): bool
    {
        $vote = $this->votes->updateOrCreate([
            'answer_id' => $answer->id,
            'user_id' => $user->id,
        ], [
            'question_id' => null,
            'is_helpful' => true,
        ]);

        return $vote->is_helpful;
    }
}
