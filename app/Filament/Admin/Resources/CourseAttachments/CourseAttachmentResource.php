<?php

namespace App\Filament\Admin\Resources\CourseAttachments;

use App\Filament\Admin\Resources\CourseAttachments\Schemas\CourseAttachmentForm;
use App\Filament\Admin\Resources\CourseAttachments\Tables\CourseAttachmentsTable;
use App\Models\CourseAttachment;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseAttachmentResource extends Resource
{
    protected static ?string $model = CourseAttachment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.attachments');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_attachment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_attachments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_curriculum_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseAttachmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseAttachmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseAttachments::route('/'),
            'create' => Pages\CreateCourseAttachment::route('/create'),
            'view' => Pages\ViewCourseAttachment::route('/{record}'),
            'edit' => Pages\EditCourseAttachment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user->hasRole('instructor')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereHas('curriculumSection.courseGroup.instructors', fn (Builder $b) => $b->whereKey($user->id))
                    ->orWhereHas('course.groups.instructors', fn (Builder $b) => $b->whereKey($user->id));
            });
        }

        return $query->with(['course', 'curriculumSection.courseGroup']);
    }
}
