<?php

namespace App\Filament\Admin\Resources\CourseGroups\Pages;

use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourseGroup extends EditRecord
{
    protected static string $resource = CourseGroupResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dateFields = ['start_at', 'end_at', 'enrollment_deadline_at'];

        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                try {
                    Carbon::parse($data[$field]);
                } catch (\Throwable) {
                    Notification::make()
                        ->danger()
                        ->title(__('admin.validation.invalid_date_field', ['field' => $field]))
                        ->send();
                    $this->halt();
                }
            }
        }

        return $data;
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
