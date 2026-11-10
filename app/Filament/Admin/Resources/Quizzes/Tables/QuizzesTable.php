<?php

namespace App\Filament\Admin\Resources\Quizzes\Tables;

use App\Models\Course;
use App\Models\Quiz;
use App\Services\Admin\QuizImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course_title'))
                ->state(fn (Quiz $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('course_group_title')
                ->label(__('admin.labels.course_group'))
                ->state(fn (Quiz $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('curriculum_section')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn (Quiz $record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('passing_score')
                ->formatStateUsing(fn ($state) => $state !== null ? $state.'%' : '-')
                ->sortable(),
            TextColumn::make('questions_count')->label(__('admin.labels.questions'))->counts('questions')->sortable(),
            TextColumn::make('attempts_count')->label(__('admin.labels.attempts'))->counts('attempts')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('course_id')
                ->relationship('course', 'slug')
                ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                ->label(__('admin.labels.course'))
                ->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
            Action::make('import_quizzes')
                ->label(__('admin.actions.import_quizzes'))
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading(__('admin.modals.quiz_import.heading'))
                ->modalDescription(__('admin.modals.quiz_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.quizzes.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e('Use the XLSX template. Keep sheet names (Quizzes, Questions, Answers) and header names unchanged.').
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.labels.import_file'))
                        ->disk('local')
                        ->directory('imports/quizzes')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(QuizImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        'Processed: '.$summary['processed'],
                        'Created: '.$summary['created'],
                        'Skipped: '.$summary['skipped'],
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 5)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.quiz_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quizzes.heading'))
            ->emptyStateDescription(__('admin.empty_states.quizzes.description'));
    }
}
