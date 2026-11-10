<?php

namespace App\Filament\Admin\Resources\CourseAttachments\Tables;

use App\Enums\AttachmentVisibility;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CourseAttachmentsTable
{
    public static function configure(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('group_title')
                ->label(__('admin.labels.group'))
                ->state(fn (CourseAttachment $record) => $record->curriculumSection?->courseGroup?->getTranslation('title', $locale) ?? '-')
                ->toggleable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn (CourseAttachment $record) => $record->curriculumSection?->getTranslation('title', $locale) ?? '-')
                ->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (CourseAttachment $record) => $record->getTranslation('title', $locale))
                ->searchable(),
            TextColumn::make('visibility')
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof AttachmentVisibility ? $state->value : (string) $state;

                    return match ($value) {
                        AttachmentVisibility::Public->value => 'success',
                        AttachmentVisibility::EnrolledOnly->value => 'warning',
                        AttachmentVisibility::Profile->value => 'info',
                        AttachmentVisibility::General->value => 'primary',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('file_path')
                ->label(__('admin.labels.file'))
                ->state(fn (CourseAttachment $record): string => __('admin.labels.files_count', ['count' => count((array) $record->file_path)]))
                ->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            SelectFilter::make('course_id')
                ->label(__('admin.labels.course'))
                ->options(fn () => Course::query()
                    ->get()
                    ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', $locale)])
                    ->all()
                )
                ->searchable(),
            SelectFilter::make('course_group_id')
                ->label(__('admin.labels.group'))
                ->options(fn () => CourseGroup::query()
                    ->with('course')
                    ->get()
                    ->mapWithKeys(fn (CourseGroup $g): array => [
                        $g->id => ($g->course?->getTranslation('title', $locale) ?? '').' — '.$g->getTranslation('title', $locale),
                    ])
                    ->all()
                )
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => isset($data['value'])
                    ? $query->whereHas('curriculumSection', fn ($q) => $q->where('course_group_id', $data['value']))
                    : $query
                ),
            SelectFilter::make('visibility')->options([
                AttachmentVisibility::Public->value => __('admin.options.public'),
                AttachmentVisibility::EnrolledOnly->value => __('admin.options.enrolled_only'),
                AttachmentVisibility::Profile->value => __('admin.options.profile'),
                AttachmentVisibility::General->value => __('admin.options.general'),
            ])->searchable(),
        ])->actions([
            Action::make('preview')
                ->label(__('admin.actions.preview'))
                ->icon('heroicon-o-eye')
                ->modalHeading(__('admin.actions.preview'))
                ->modalContent(function (CourseAttachment $record): HtmlString {
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
                ->visible(fn (CourseAttachment $record): bool => filled($record->file_path)),
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_attachments.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_attachments.description'));
    }
}
