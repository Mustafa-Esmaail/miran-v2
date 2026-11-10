<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use App\Models\CourseCurriculumSection;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_sessions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.session'))
                ->schema([
                    TextInput::make('title')->placeholder(__('admin.placeholders.live_session')),
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
                    RichEditor::make('content')
                        ->label(__('admin.labels.content'))
                        ->columnSpanFull(),
                    DateTimePicker::make('start_at')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('duration_minutes')->numeric()->required()->label(__('admin.labels.duration_min')),
                    Textarea::make('join_url')
                        ->label(__('admin.labels.join_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->rows(3)
                        ->rule('url'),
                    Textarea::make('recording_url')
                        ->label(__('admin.labels.recording_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->rows(3)
                        ->rule('url'),
                    FileUpload::make('attachments')
                        ->label(__('admin.labels.attachments'))
                        ->disk('s3')
                        ->directory('course-sessions/attachments')
                        ->visibility('private')
                        ->multiple()
                        ->reorderable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn ($record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('start_at')->dateTime('Y-m-d H:i'),
            TextColumn::make('duration_minutes')
                ->label(__('admin.labels.duration'))
                ->formatStateUsing(fn ($state) => $state ? $state.' min' : '-'),
            TextColumn::make('attachments')
                ->label(__('admin.labels.attachments'))
                ->state(fn ($record) => count($record->attachments ?? [])),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseGroupSessionResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseGroupSessionResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_sessions.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_sessions.description'));
    }
}
