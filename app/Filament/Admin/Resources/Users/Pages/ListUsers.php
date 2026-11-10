<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Exports\UsersExport;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Services\Admin\UserImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),

            Action::make('export_users')
                ->label(__('admin.actions.export_users'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => Excel::download(new UsersExport($this->getFilteredTableQuery()), 'users-'.now()->format('Y-m-d').'.xlsx')),

            Action::make('import_users')
                ->label(__('admin.actions.import_users'))
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading(__('admin.modals.user_import.heading'))
                ->modalDescription(__('admin.modals.user_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.users.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e(__('admin.helpers.user_import_template_help')).
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.fields.import_file'))
                        ->disk('local')
                        ->directory('imports/users')
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
                    $summary = app(UserImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        __('admin.notifications.users_import_summary', ['processed' => $summary['processed']]),
                        __('admin.notifications.users_import_created', ['count' => $summary['created']]),
                        __('admin.notifications.users_import_updated', ['count' => $summary['updated']]),
                        $summary['mail_sent'] > 0 ? __('admin.notifications.users_import_mail_sent', ['count' => $summary['mail_sent']]) : null,
                        $summary['mail_failed'] > 0 ? __('admin.notifications.users_import_mail_failed', ['count' => $summary['mail_failed']]) : null,
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 3)) : null,
                        ! empty($summary['mail_errors']) ? implode(PHP_EOL, array_slice($summary['mail_errors'], 0, 2)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.users_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }
}
