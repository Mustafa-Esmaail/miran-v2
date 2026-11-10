<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Filament\Admin\Resources\CourseCurriculumSections\CourseCurriculumSectionResource;
use App\Filament\Admin\Resources\CourseCurriculumSections\Schemas\CourseCurriculumSectionForm;
use App\Filament\Forms\TranslatableField;
use App\Models\CourseGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurriculumSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'curriculumSections';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_curriculum_sections');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.content'))
                ->schema([
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(function (RelationManager $livewire): array {
                            return CourseGroup::query()
                                ->where('course_id', $livewire->getOwnerRecord()->id)
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                                ])
                                ->all();
                        })
                        ->required()
                        ->searchable()
                        ->columnSpanFull(),
                    TranslatableField::localeSwitcher(),
                    TextInput::make('sort_order')
                        ->label(__('admin.labels.sort_order'))
                        ->numeric()
                        ->default(0)
                        ->required(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TranslatableField::rich('description', __('admin.fields.description'))->columnSpanFull(),
                ])
                ->columns(2),
            CourseCurriculumSectionForm::bootcampSection(
                fn (): bool => $this->getOwnerRecord()->template?->isBootcamp() ?? false
            ),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('group_title')
                    ->label(__('admin.labels.group'))
                    ->state(fn ($record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->sortable(false),
                TextColumn::make('title')
                    ->label(__('admin.labels.title'))
                    ->state(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                    ->searchable(),
                TextColumn::make('sort_order')->label(__('admin.labels.sort_order'))->sortable(),
                TextColumn::make('lessons_count')->label(__('admin.labels.lessons'))->counts('lessons'),
                TextColumn::make('sessions_count')->label(__('admin.labels.sessions'))->counts('sessions'),
                TextColumn::make('attachments_count')->label(__('admin.labels.attachments'))->counts('attachments'),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseCurriculumSectionResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseCurriculumSectionResource::getUrl('edit', ['record' => $record]));
    }
}
