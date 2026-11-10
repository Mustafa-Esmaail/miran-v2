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

class QuizQuestionsImportService
{
    public const SHEET_QUESTIONS = 'test';

    /**
     * @return array{processed:int,created:int,skipped:int,errors:list<string>}
     */
    public function import(Quiz $quiz, string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);

        if ($reader instanceof Csv) {
            $reader->setInputEncoding(Csv::GUESS_ENCODING)->setFallbackEncoding('CP1256');
        }

        $spreadsheet = $reader->load($absolutePath);
        $sheet = $spreadsheet->getSheetByName(self::SHEET_QUESTIONS);

        if (! $sheet) {
            return ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => ['Sheet "'.self::SHEET_QUESTIONS.'" not found in the file.']];
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $dataRows = array_values(array_filter(
            array_map(fn ($r) => $this->mapRow($headers, $r), $rows),
            fn ($r) => ! $this->rowIsEmpty($r)
        ));

        $summary = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($dataRows as $index => $row) {
            $summary['processed']++;
            $rowNum = $index + 2; // +2: row 1 is the header

            try {
                DB::transaction(function () use ($quiz, $row): void {
                    $question = $this->importQuestionRow($quiz, $row);
                    $this->importAnswersFromRow($question, $row);
                });

                $summary['created']++;
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = 'Row '.$rowNum.': '.$e->getMessage();
            }
        }

        return $summary;
    }

    /**
     * V2 flat format columns: question_row (text), point, label (type), 1-4 (options), is_correct (1-based index).
     *
     * @param  array<string, string|null>  $row
     */
    private function importQuestionRow(Quiz $quiz, array $row): QuizQuestion
    {
        $row['label'] = $this->normalizeType((string) ($row['label'] ?? ''));

        $validated = Validator::make($row, [
            'question_row' => ['required', 'string'],
            'label' => ['required', 'in:mcq,true_false'],
            'point' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        return QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => trim((string) $validated['question_row']),
            'type' => QuizQuestionType::from($validated['label']),
            'points' => isset($validated['point']) ? (int) $validated['point'] : 1,
        ]);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importAnswersFromRow(QuizQuestion $question, array $row): void
    {
        $correctIndex = (int) ($row['is_correct'] ?? 0);

        foreach (['1', '2', '3', '4'] as $pos) {
            $label = trim((string) ($row[$pos] ?? ''));

            if ($label === '') {
                continue;
            }

            QuizOption::query()->create([
                'quiz_question_id' => $question->id,
                'label' => $label,
                'is_correct' => (int) $pos === $correctIndex,
            ]);
        }
    }

    private function normalizeType(string $type): string
    {
        $lower = strtolower(trim($type));

        return match ($lower) {
            'true/false', 'true_false' => 'true_false',
            default => $lower,
        };
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
