<?php

namespace App\Filament\Admin\Resources\Enrollments\Pages;

use App\Exports\EnrollmentsExport;
use App\Filament\Admin\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\Settings\SettingsService;
use App\Support\Exports\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label(__('admin.actions.export_csv'))
                ->action(fn (): StreamedResponse => $this->exportCsv()),
            Action::make('export_xlsx')
                ->label(__('admin.actions.export_xlsx'))
                ->action(fn (): BinaryFileResponse => $this->exportXlsx()),
            // PDF export disabled.
        ];
    }

    private function exportCsv(): StreamedResponse
    {
        $headers = [
            'User',
            'Email',
            'Course',
            'Course Group',
            'Status',
            'Invited',
            'Activated At',
            'Created At',
        ];

        $rows = (clone $this->getFilteredTableQuery())
            ->reorder('id')
            ->lazy()
            ->map(function (Enrollment $enrollment): array {
                return [
                    $enrollment->user?->name ?? '',
                    $enrollment->user?->email ?? '',
                    $enrollment->course?->getTranslation('title', app()->getLocale()) ?? '',
                    $enrollment->courseGroup?->getTranslation('title', app()->getLocale()) ?? '',
                    $enrollment->status?->value ?? (string) $enrollment->status,
                    $enrollment->is_invited ? 'yes' : 'no',
                    $enrollment->activated_at?->format('Y-m-d H:i'),
                    $enrollment->created_at?->format('Y-m-d H:i'),
                ];
            });

        return app(CsvExport::class)->stream($this->exportFilename('enrollments', 'csv'), $headers, $rows);
    }

    private function exportXlsx(): BinaryFileResponse
    {
        return Excel::download(new EnrollmentsExport($this->getFilteredTableQuery()), $this->exportFilename('enrollments', 'xlsx'));
    }

    private function exportPdf(): Response
    {
        $filename = $this->exportFilename('enrollments', 'pdf');
        $enrollments = (clone $this->getFilteredTableQuery())
            ->reorder('id')
            ->get();

        $logoUrl = app(SettingsService::class)->logoUrl();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
            ->loadView('exports.enrollments', [
                'logoUrl' => $logoUrl,
                'rows' => $enrollments,
                'generatedAt' => now()->format('Y-m-d H:i'),
            ]);

        return $pdf->download($filename);
    }

    private function exportFilename(string $module, string $extension): string
    {
        $timestamp = now()->format('Ymd-His');

        return "miran-{$module}-{$timestamp}.{$extension}";
    }
}
