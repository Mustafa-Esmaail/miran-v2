<?php

namespace App\Filament\Admin\Resources\CourseDiscounts\Pages;

use App\Filament\Admin\Resources\CourseDiscounts\CourseDiscountResource;
use Filament\Resources\Pages\EditRecord;

class EditCourseDiscount extends EditRecord
{
    protected static string $resource = CourseDiscountResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
