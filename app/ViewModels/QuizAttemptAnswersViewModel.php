<?php

namespace App\ViewModels;

use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Support\Collection;

class QuizAttemptAnswersViewModel
{
    /**
     * @return array{
     *     items: array<int, array{
     *         number: int,
     *         question_id: int,
     *         question: string,
     *         points: int,
     *         selected_answer: string|null,
     *         correct_answer: string|null,
     *         is_answered: bool,
     *         is_correct: bool,
     *         has_missing_option: bool
     *     }>,
     *     extra_answers: array<int, array{question_id: string, answer: string}>,
     *     answered_count: int,
     *     correct_count: int,
     *     question_count: int,
     *     raw_json: string
     * }
     */
    public static function fromAttempt(QuizAttempt $attempt): array
    {
        $attempt->loadMissing(['quiz.questions.options']);

        $answers = is_array($attempt->answers) ? $attempt->answers : [];
        $questions = $attempt->quiz?->questions ?? collect();

        $items = $questions
            ->map(fn (QuizQuestion $question, int $index): array => self::makeItem($question, $index, $answers))
            ->values();

        $questionIds = $questions
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        $extraAnswers = collect($answers)
            ->reject(fn (mixed $answer, int|string $questionId): bool => in_array((string) $questionId, $questionIds, true))
            ->map(fn (mixed $answer, int|string $questionId): array => [
                'question_id' => (string) $questionId,
                'answer' => self::displayValue($answer),
            ])
            ->values();

        return [
            'items' => $items->all(),
            'extra_answers' => $extraAnswers->all(),
            'answered_count' => $items->where('is_answered', true)->count(),
            'correct_count' => $items->where('is_correct', true)->count(),
            'question_count' => $questions->count(),
            'raw_json' => json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '[]',
        ];
    }

    /**
     * @param  array<int|string, mixed>  $answers
     * @return array{
     *     number: int,
     *     question_id: int,
     *     question: string,
     *     points: int,
     *     selected_answer: string|null,
     *     correct_answer: string|null,
     *     is_answered: bool,
     *     is_correct: bool,
     *     has_missing_option: bool
     * }
     */
    private static function makeItem(QuizQuestion $question, int $index, array $answers): array
    {
        $selectedAnswer = self::answerForQuestion($answers, $question->id);
        $selectedOption = self::selectedOption($question->options, $selectedAnswer);
        $correctOption = $question->options->firstWhere('is_correct', true);
        $isAnswered = $selectedAnswer !== null && $selectedAnswer !== '';

        return [
            'number' => $index + 1,
            'question_id' => $question->id,
            'question' => $question->question,
            'points' => $question->points,
            'selected_answer' => $isAnswered ? ($selectedOption?->label ?? self::displayValue($selectedAnswer)) : null,
            'correct_answer' => $correctOption?->label,
            'is_answered' => $isAnswered,
            'is_correct' => $selectedOption !== null && $correctOption !== null && $selectedOption->is($correctOption),
            'has_missing_option' => $isAnswered && $selectedOption === null,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $answers
     */
    private static function answerForQuestion(array $answers, int $questionId): mixed
    {
        if (array_key_exists($questionId, $answers)) {
            return $answers[$questionId];
        }

        if (array_key_exists((string) $questionId, $answers)) {
            return $answers[(string) $questionId];
        }

        return null;
    }

    /**
     * @param  Collection<int, QuizOption>  $options
     */
    private static function selectedOption(Collection $options, mixed $answer): ?QuizOption
    {
        if (! is_scalar($answer) || $answer === '') {
            return null;
        }

        return $options->firstWhere('id', (int) $answer);
    }

    private static function displayValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
