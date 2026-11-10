<?php

namespace App\Filament\Admin\Resources\CourseDiscounts\Pages;

use App\Filament\Admin\Resources\CourseDiscounts\CourseDiscountResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseDiscounts extends ListRecords
{
    protected static string $resource = CourseDiscountResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
