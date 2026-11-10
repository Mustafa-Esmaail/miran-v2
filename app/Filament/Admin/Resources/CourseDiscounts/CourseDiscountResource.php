<?php

namespace App\Filament\Admin\Resources\CourseDiscounts;

use App\Filament\Admin\Resources\CourseDiscounts\Schemas\CourseDiscountForm;
use App\Filament\Admin\Resources\CourseDiscounts\Tables\CourseDiscountsTable;
use App\Models\Discount;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseDiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_discounts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_discount');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_discounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_catalog_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseDiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseDiscountsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('courses')->withCount('courses');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseDiscounts::route('/'),
            'create' => Pages\CreateCourseDiscount::route('/create'),
            'view' => Pages\ViewCourseDiscount::route('/{record}'),
            'edit' => Pages\EditCourseDiscount::route('/{record}/edit'),
        ];
    }
}
