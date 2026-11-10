<?php

namespace App\Filament\Admin\Resources\Quizzes\RelationManagers;

use App\Enums\QuizQuestionType;
use App\Filament\Admin\Resources\QuizQuestions\QuizQuestionResource;
use App\Models\Quiz;
use App\Services\Admin\QuizQuestionsImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.quiz_questions');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('admin')->user()?->can('quizzes.viewAny') ?? false;
    }

    public function canCreate(): bool
    {
        return auth('admin')->user()?->can('quizzes.update') ?? false;
    }

    public function canEdit(Model $record): bool
    {
        return auth('admin')->user()?->can('quizzes.update') ?? false;
    }

    public function canDelete(Model $record): bool
    {
        return auth('admin')->user()?->can('quizzes.delete') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.question'))
                ->columnSpanFull()
                ->schema([
                    Textarea::make('question')
                        ->required()
                        ->rows(3),
                    Select::make('type')
                        ->options([
                            QuizQuestionType::Mcq->value => __('admin.options.multiple_choice'),
                            QuizQuestionType::TrueFalse->value => __('admin.options.true_false'),
                        ])
                        ->required(),
                    TextInput::make('points')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    Repeater::make('options')
                        ->relationship()
                        ->schema([
                            TextInput::make('label')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('is_correct')
                                ->label(__('admin.labels.correct'))
                                ->default(false),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->defaultItems(2)
                        ->helperText(__('admin.helpers.mark_one_or_more_correct_options')),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('question')->limit(60)->searchable(),
            TextColumn::make('type')->sortable(),
            TextColumn::make('points')->sortable(),
            TextColumn::make('options_count')->label(__('admin.labels.options'))->counts('options')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create'))
                ->modal(false)
                ->url(fn () => QuizQuestionResource::getUrl('create', [
                    'quiz_id' => $this->getOwnerRecord()->id,
                ])),
            Action::make('import_questions')
                ->label(__('admin.actions.import_questions'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => auth('admin')->user()?->can('quizzes.update') ?? false)
                ->modalHeading(__('admin.modals.quiz_questions_import.heading'))
                ->modalDescription(__('admin.modals.quiz_questions_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.quiz-questions.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e('Use the XLSX template (v2). Single sheet "test" — columns: question_row, point, label (mcq/TRUE/FALSE), 1–4 (options), is_correct (1-based).').
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.labels.import_file'))
                        ->disk('local')
                        ->directory('imports/quiz-questions')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Quiz $quiz */
                    $quiz = $this->getOwnerRecord();
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(QuizQuestionsImportService::class)->import($quiz, $disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        'Processed: '.$summary['processed'],
                        'Created: '.$summary['created'],
                        'Skipped: '.$summary['skipped'],
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 5)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.quiz_questions_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ])->actions([
            EditAction::make()->label(__('admin.actions.edit'))
                ->modal(false)
                ->url(fn ($record) => QuizQuestionResource::getUrl('edit', ['record' => $record])),
            DeleteAction::make()->label(__('admin.actions.delete')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quiz_questions.heading'))
            ->emptyStateDescription(__('admin.empty_states.quiz_questions.description'));
    }
}
