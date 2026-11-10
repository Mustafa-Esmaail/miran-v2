<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Exports\ServiceRequestsExport;
use App\Services\Admin\ServiceRequestImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait HasServiceRequestListHeaderActions
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),

            Action::make('export_xlsx')
                ->label(__('admin.actions.export_xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => Excel::download(new ServiceRequestsExport($this->getFilteredTableQuery()), 'service-requests-'.now()->format('Y-m-d').'.xlsx')),

            Action::make('import_service_requests')
                ->label(__('admin.actions.import_service_requests'))
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading(__('admin.modals.service_request_import.heading'))
                ->modalDescription(__('admin.modals.service_request_import.description'))
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitActionLabel(__('admin.lead_import.actions.confirm'))
                ->steps([
                    Step::make('upload')
                        ->label(__('admin.lead_import.steps.upload'))
                        ->schema([
                            Placeholder::make('template')
                                ->label(__('admin.fields.reference_file'))
                                ->content(new HtmlString(
                                    '<a href="'.route('admin.service-requests.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                                    e(__('admin.actions.download_template')).
                                    '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                                    e(__('admin.helpers.service_request_import_template_help')).
                                    '</p>'
                                ))
                                ->columnSpanFull(),
                            FileUpload::make('file')
                                ->label(__('admin.fields.import_file'))
                                ->disk('local')
                                ->directory('imports/service-requests')
                                ->acceptedFileTypes([
                                    'text/csv',
                                    'text/plain',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->required(),
                        ])
                        ->afterValidation(function (Get $get, Set $set): void {
                            $path = $this->resolveLeadImportFilePath($get('file'));

                            $set('preview', $path === null ? null : $this->buildLeadImportPreview(
                                app(ServiceRequestImportService::class)->analyse($path),
                            ));
                        }),
                    Step::make('preview')
                        ->label(__('admin.lead_import.steps.preview'))
                        ->schema([
                            Hidden::make('preview')
                                ->dehydrated(false),
                            View::make('filament.resources.service-requests.import.preview')
                                ->viewData(fn (Get $get): array => ['preview' => $get('preview')])
                                ->columnSpanFull(),
                            Toggle::make('skip_duplicates')
                                ->label(__('admin.lead_import.fields.skip_duplicates'))
                                ->helperText(__('admin.lead_import.helpers.skip_duplicates'))
                                ->default(true),
                        ]),
                ])
                ->action(function (array $data): void {
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(ServiceRequestImportService::class)->import(
                        $disk->path($file),
                        (bool) ($data['skip_duplicates'] ?? true),
                    );
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        __('admin.notifications.service_requests_import_summary', ['processed' => $summary['processed'], 'created' => $summary['created'], 'skipped' => $summary['skipped']]),
                        $summary['duplicates_skipped'] > 0 ? __('admin.lead_import.notifications.duplicates_skipped', ['count' => $summary['duplicates_skipped']]) : null,
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 5)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.service_requests_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }

    /**
     * The local path of the file chosen in the upload step (a temporary upload before the
     * action is submitted, a stored path after).
     */
    private function resolveLeadImportFilePath(mixed $state): ?string
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        if (is_string($file) && str_starts_with($file, 'livewire-file:')) {
            $file = TemporaryUploadedFile::createFromLivewire($file);
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath() ?: null;
        }

        if (is_string($file) && filled($file)) {
            return Storage::disk('local')->path($file);
        }

        return null;
    }

    /**
     * Keeps only what the preview step shows, so the modal state stays small.
     *
     * @param  array{rows: list<array{row: int, status: string, reason: string|null, data: array<string, mixed>}>, counts: array<string, int>}  $analysis
     * @return array{counts: array<string, int>, problems: list<array{row: int, status: string, name: string|null, phone: string|null, reason: string|null}>, more_problems: int}
     */
    private function buildLeadImportPreview(array $analysis): array
    {
        $problems = array_values(array_filter(
            $analysis['rows'],
            fn (array $row): bool => $row['status'] !== ServiceRequestImportService::STATUS_NEW,
        ));

        return [
            'counts' => $analysis['counts'],
            'problems' => array_map(fn (array $row): array => [
                'row' => $row['row'],
                'status' => $row['status'],
                'name' => $row['data']['name'] ?? null,
                'phone' => $row['data']['phone'] ?? null,
                'reason' => $row['reason'],
            ], array_slice($problems, 0, 20)),
            'more_problems' => max(0, count($problems) - 20),
        ];
    }
}
