<?php

namespace App\Filament\Admin\Resources\CourseReviews\Pages;

use App\Filament\Admin\Resources\CourseReviews\CourseReviewResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseReview extends ViewRecord
{
    protected static string $resource = CourseReviewResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
