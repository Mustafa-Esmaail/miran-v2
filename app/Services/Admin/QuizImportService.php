<?php

namespace App\Services\Admin;

use App\Enums\QuizQuestionType;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class QuizImportService
{
    /**
     * Sheet names expected in the workbook.
     */
    public const SHEET_QUIZZES = 'Quizzes';

    public const SHEET_QUESTIONS = 'Questions';

    public const SHEET_ANSWERS = 'Answers';

    /**
     * @return array{processed:int,created:int,skipped:int,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);

        if ($reader instanceof Csv) {
            $reader->setInputEncoding(Csv::GUESS_ENCODING)->setFallbackEncoding('CP1256');
        }

        $spreadsheet = $reader->load($absolutePath);

        $quizSheet = $spreadsheet->getSheetByName(self::SHEET_QUIZZES);
        $questionSheet = $spreadsheet->getSheetByName(self::SHEET_QUESTIONS);
        $answerSheet = $spreadsheet->getSheetByName(self::SHEET_ANSWERS);

        if (! $quizSheet) {
            return ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => ['Sheet "Quizzes" not found in the file.']];
        }

        $quizRows = $quizSheet->toArray(null, true, true, false);
        $questionRows = $questionSheet ? $questionSheet->toArray(null, true, true, false) : [];
        $answerRows = $answerSheet ? $answerSheet->toArray(null, true, true, false) : [];

        $quizHeaders = $this->normalizeHeaders(array_shift($quizRows) ?? []);
        $questionHeaders = $this->normalizeHeaders(array_shift($questionRows) ?? []);
        $answerHeaders = $this->normalizeHeaders(array_shift($answerRows) ?? []);

        // Build indexed maps: row_number (1-based, not counting header) => row data
        $quizDataRows = array_values(array_filter(
            array_map(fn ($r) => $this->mapRow($quizHeaders, $r), $quizRows),
            fn ($r) => ! $this->rowIsEmpty($r)
        ));

        $questionDataRows = array_values(array_filter(
            array_map(fn ($r) => $this->mapRow($questionHeaders, $r), $questionRows),
            fn ($r) => ! $this->rowIsEmpty($r)
        ));

        $answerDataRows = array_values(array_filter(
            array_map(fn ($r) => $this->mapRow($answerHeaders, $r), $answerRows),
            fn ($r) => ! $this->rowIsEmpty($r)
        ));

        $summary = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($quizDataRows as $quizIndex => $quizRow) {
            $summary['processed']++;
            $quizRef = $quizIndex + 1; // 1-based

            try {
                DB::transaction(function () use ($quizRow, $quizRef, $questionDataRows, $answerDataRows): void {
                    $quiz = $this->importQuizRow($quizRow);

                    foreach ($questionDataRows as $qIndex => $questionRow) {
                        if ((int) ($questionRow['quiz_row'] ?? 0) !== $quizRef) {
                            continue;
                        }

                        $questionRef = $qIndex + 1;
                        $question = $this->importQuestionRow($quiz, $questionRow);

                        foreach ($answerDataRows as $answerRow) {
                            if ((int) ($answerRow['question_row'] ?? 0) !== $questionRef) {
                                continue;
                            }

                            $this->importAnswerRow($question, $answerRow);
                        }
                    }
                });

                $summary['created']++;
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = 'Quiz row '.($quizRef + 1).': '.$e->getMessage();
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importQuizRow(array $row): Quiz
    {
        $validated = Validator::make($row, [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'course_group_id' => ['required', 'integer', 'exists:course_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ])->validate();

        return Quiz::query()->create([
            'course_id' => (int) $validated['course_id'],
            'course_group_id' => (int) $validated['course_group_id'],
            'title' => trim((string) $validated['title']),
            'passing_score' => isset($validated['passing_score']) ? (int) $validated['passing_score'] : 0,
        ]);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importQuestionRow(Quiz $quiz, array $row): QuizQuestion
    {
        $validated = Validator::make($row, [
            'question' => ['required', 'string'],
            'type' => ['required', 'in:mcq,true_false'],
            'points' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        return QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => trim((string) $validated['question']),
            'type' => QuizQuestionType::from($validated['type']),
            'points' => isset($validated['points']) ? (int) $validated['points'] : 1,
        ]);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importAnswerRow(QuizQuestion $question, array $row): QuizOption
    {
        $validated = Validator::make($row, [
            'label' => ['required', 'string', 'max:500'],
            'is_correct' => ['nullable'],
        ])->validate();

        $isCorrect = in_array((string) ($validated['is_correct'] ?? '0'), ['1', 'true', 'yes', 'صح'], true);

        return QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => trim((string) $validated['label']),
            'is_correct' => $isCorrect,
        ]);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($h): string => (string) Str::of((string) $h)
            ->replace("\xEF\xBB\xBF", '')
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_'), $headers);
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $value = $row[$index] ?? null;
            $mapped[$header] = is_string($value) ? trim($value) : (filled($value) ? (string) $value : null);
        }

        return $mapped;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }
}
