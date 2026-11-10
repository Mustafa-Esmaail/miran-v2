<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Enums\LessonType;
use App\Filament\Admin\Resources\Lessons\LessonResource;
use App\Filament\Forms\TranslatableField;
use App\Models\CourseCurriculumSection;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_lessons');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.lesson'))
                ->schema([
                    TranslatableField::localeSwitcher(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(fn () => CourseCurriculumSection::query()
                            ->where('course_id', $this->getOwnerRecord()->id)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (CourseCurriculumSection $section) => [
                                $section->id => $section->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->required(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TranslatableField::rich('content', __('admin.fields.content'))->columnSpanFull(),
                    Select::make('type')->options([
                        'pdf' => __('admin.options.pdf'),
                        'text' => __('admin.options.text'),
                        'video' => __('admin.options.video'),
                    ])->required(),
                    FileUpload::make('pdf_path')->disk('s3')
                        ->directory('lesson-pdfs')
                        ->visibility('private'),
                    TextInput::make('video_url')->url()->placeholder(__('admin.placeholders.https')),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->nullable()
                        ->helperText(__('admin.helpers.lower_numbers_appear_first')),
                    Select::make('is_preview')->options([0 => __('admin.options.no'), 1 => __('admin.options.yes')])->label(__('admin.labels.preview')),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->formatStateUsing(fn ($state, $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('type')
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof LessonType ? $state->value : (string) $state;

                    return match ($value) {
                        LessonType::Video->value => 'primary',
                        LessonType::Pdf->value => 'gray',
                        LessonType::Text->value => 'info',
                        default => 'secondary',
                    };
                }),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn ($record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('sort_order')->sortable(),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => LessonResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => LessonResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_lessons.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_lessons.description'));
    }
}
