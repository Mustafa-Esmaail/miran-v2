<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\Pages;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use App\Services\ZoomService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourseGroupSession extends EditRecord
{
    protected static string $resource = CourseGroupSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createZoomMeeting')
                ->label(__('admin.actions.create_zoom_meeting'))
                ->icon('heroicon-o-video-camera')
                ->visible(fn (): bool => auth('admin')->user()?->can('zoom.create_meetings') ?? false)
                ->action(function (ZoomService $zoom): void {
                    $session = $this->getRecord();
                    $zoom->createMeeting($session);
                    $this->refreshFormData(['join_url', 'zoom_start_url', 'zoom_meeting_id', 'zoom_meeting_uuid', 'zoom_synced_at']);

                    Notification::make()
                        ->success()
                        ->title(__('admin.notifications.zoom_meeting_created'))
                        ->send();
                }),
            Action::make('syncZoomRegistrants')
                ->label(__('admin.actions.sync_zoom_registrants'))
                ->icon('heroicon-o-user-plus')
                ->visible(fn (): bool => (bool) ($this->getRecord()->zoom_meeting_id) && (auth('admin')->user()?->can('zoom.create_meetings') ?? false))
                ->action(function (ZoomService $zoom): void {
                    $zoom->syncRegistrants($this->getRecord());

                    Notification::make()
                        ->success()
                        ->title(__('admin.notifications.zoom_registrants_synced'))
                        ->send();
                }),
            Action::make('syncZoomData')
                ->label(__('admin.actions.sync_zoom_data'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => auth('admin')->user()?->can('zoom.sync_attendance') ?? false)
                ->action(function (ZoomService $zoom): void {
                    $zoom->syncSessionAttendance($this->getRecord());
                    $this->refreshFormData(['recording_url', 'zoom_synced_at']);

                    Notification::make()
                        ->success()
                        ->title(__('admin.notifications.zoom_sync_completed'))
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['start_at'])) {
            try {
                Carbon::parse($data['start_at']);
            } catch (\Throwable) {
                Notification::make()
                    ->danger()
                    ->title(__('admin.validation.invalid_start_at'))
                    ->send();
                $this->halt();
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
