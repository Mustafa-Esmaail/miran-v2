<?php

namespace App\Filament\Admin\Resources\CourseReviews;

use App\Filament\Admin\Resources\CourseReviews\Schemas\CourseReviewForm;
use App\Filament\Admin\Resources\CourseReviews\Tables\CourseReviewsTable;
use App\Models\CourseReview;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseReviewResource extends Resource
{
    protected static ?string $model = CourseReview::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_reviews');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_review');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_reviews');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_engagement_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseReviewsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseReviews::route('/'),
            'view' => Pages\ViewCourseReview::route('/{record}'),
        ];
    }
}
