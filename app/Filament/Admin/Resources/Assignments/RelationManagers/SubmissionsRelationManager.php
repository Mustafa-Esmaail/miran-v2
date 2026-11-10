<?php

namespace App\Filament\Admin\Resources\Assignments\RelationManagers;

use App\Enums\AssignmentSubmissionStatus;
use App\Models\AssignmentSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.assignment_submissions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('enrollment.user.name')
                    ->label(__('admin.labels.student'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollment.user.email')
                    ->label(__('admin.labels.email'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('submitted_at')
                    ->label(__('admin.labels.submitted_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->color(fn (AssignmentSubmissionStatus $state): string => match ($state) {
                        AssignmentSubmissionStatus::Graded => 'success',
                        AssignmentSubmissionStatus::Submitted => 'warning',
                    }),
                TextColumn::make('grade')
                    ->label(__('admin.labels.grade'))
                    ->state(fn (AssignmentSubmission $record): string => $record->grade === null
                        ? '-'
                        : "{$record->grade}/{$record->assignment->max_grade}"),
                TextColumn::make('graded_by')
                    ->label(__('admin.labels.graded_by'))
                    ->state(fn (AssignmentSubmission $record): string => $record->grader?->name ?? '-')
                    ->toggleable(),
            ])
            ->actions([
                Action::make('files')
                    ->label(__('admin.actions.preview'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('admin.actions.preview'))
                    ->modalContent(function (AssignmentSubmission $record): HtmlString {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                        $disk = Storage::disk('s3');
                        $links = collect((array) $record->file_path)
                            ->filter()
                            ->map(function (string $path) use ($disk): string {
                                $url = rescue(fn () => $disk->temporaryUrl($path, now()->addMinutes(30)), null, false);
                                $name = basename(str_replace('_', ' ', $path));

                                return $url
                                    ? '<p class="py-1"><a href="'.e($url).'" target="_blank" class="text-primary-600 underline">'.e($name).'</a></p>'
                                    : '<p class="py-1 text-gray-400">'.e($name).'</p>';
                            })
                            ->implode('');

                        return new HtmlString('<div class="space-y-1">'.$links.'</div>');
                    })
                    ->modalSubmitAction(false)
                    ->visible(fn (): bool => auth('admin')->user()?->can('assignment_submissions.view') ?? false),
                Action::make('grade')
                    ->label(__('admin.actions.grade'))
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(__('admin.actions.grade'))
                    ->schema([
                        TextInput::make('grade')
                            ->label(__('admin.labels.grade'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (AssignmentSubmission $record): int => $record->assignment->max_grade)
                            ->required(),
                        Textarea::make('feedback')
                            ->label(__('admin.labels.feedback'))
                            ->rows(4),
                    ])
                    ->fillForm(fn (AssignmentSubmission $record): array => [
                        'grade' => $record->grade,
                        'feedback' => $record->feedback,
                    ])
                    ->action(function (AssignmentSubmission $record, array $data): void {
                        $record->update([
                            'grade' => $data['grade'],
                            'feedback' => $data['feedback'] ?? null,
                            'graded_by' => auth('admin')->id(),
                            'graded_at' => now(),
                            'status' => AssignmentSubmissionStatus::Graded,
                        ]);
                    })
                    ->visible(fn (): bool => auth('admin')->user()?->can('assignment_submissions.grade') ?? false),
            ])
            ->headerActions([])
            ->defaultSort('submitted_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.assignment_submissions.heading'))
            ->emptyStateDescription(__('admin.empty_states.assignment_submissions.description'));
    }
}
