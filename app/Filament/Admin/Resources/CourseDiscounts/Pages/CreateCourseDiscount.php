<?php

namespace App\Filament\Admin\Resources\CourseDiscounts\Pages;

use App\Filament\Admin\Resources\CourseDiscounts\CourseDiscountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseDiscount extends CreateRecord
{
    protected static string $resource = CourseDiscountResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
