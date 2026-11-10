<?php

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Services\Admin\EmployeeImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
            Action::make('import_employees')
                ->label(__('admin.actions.import_employees'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => auth('admin')->user()?->can('employees.create') ?? false)
                ->modalHeading(__('admin.modals.employee_import.heading'))
                ->modalDescription(__('admin.modals.employee_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.employees.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e(__('admin.helpers.employee_import_template_help')).
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.fields.import_file'))
                        ->disk('local')
                        ->directory('imports/employees')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(EmployeeImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        __('admin.notifications.employees_import_summary', ['processed' => $summary['processed']]),
                        __('admin.notifications.employees_import_created', ['count' => $summary['created']]),
                        __('admin.notifications.employees_import_updated', ['count' => $summary['updated']]),
                        $summary['mail_sent'] > 0 ? __('admin.notifications.employees_import_mail_sent', ['count' => $summary['mail_sent']]) : null,
                        $summary['mail_failed'] > 0 ? __('admin.notifications.employees_import_mail_failed', ['count' => $summary['mail_failed']]) : null,
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 3)) : null,
                        ! empty($summary['mail_errors']) ? implode(PHP_EOL, array_slice($summary['mail_errors'], 0, 2)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.employees_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }
}
