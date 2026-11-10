<?php

namespace App\Filament\Pages\Concerns;

use App\Exports\ReportArrayExport;
use App\Services\Settings\SettingsService;
use App\Support\Exports\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV / XLSX / PDF header actions for report pages. Exports reflect the current
 * filters — the using page builds plain (unformatted) rows from its own data.
 */
trait HasReportExports
{
    /**
     * Module slug used in the download filename, e.g. "sales-performance".
     */
    abstract protected function exportName(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function exportHeadings(): array;

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    abstract protected function exportRows(): array;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label(__('admin.actions.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => app(CsvExport::class)->stream(
                    $this->exportFilename('csv'),
                    $this->exportHeadings(),
                    $this->exportRows(),
                )),
            Action::make('export_xlsx')
                ->label(__('admin.actions.export_xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new ReportArrayExport($this->exportHeadings(), $this->exportRows()),
                    $this->exportFilename('xlsx'),
                )),
            Action::make('export_pdf')
                ->label(__('admin.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn (): StreamedResponse => $this->downloadReportPdf()),
        ];
    }

    protected function downloadReportPdf(): StreamedResponse
    {
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
            ->setPaper('a4', count($this->exportHeadings()) > 6 ? 'landscape' : 'portrait')
            ->loadView('exports.report', [
                'title' => $this->getTitle(),
                'period' => $this->exportPeriodLabel(),
                'headings' => $this->exportHeadings(),
                'rows' => $this->exportRows(),
                'logoUrl' => app(SettingsService::class)->logoUrl(),
                'generatedAt' => now()->format('Y-m-d H:i'),
                'isRtl' => app()->getLocale() === 'ar',
            ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->exportFilename('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * The filtered date range shown under the PDF title, when the page has one.
     */
    protected function exportPeriodLabel(): ?string
    {
        $filters = $this->filters ?? [];
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        if (blank($from) && blank($to)) {
            return property_exists($this, 'filters') ? __('admin.report.all_time') : null;
        }

        return __('admin.report.period', ['from' => $from ?: '…', 'to' => $to ?: '…']);
    }

    private function exportFilename(string $extension): string
    {
        return 'miran-'.$this->exportName().'-'.now()->format('Ymd-His').'.'.$extension;
    }

    private function formatExportRate(?float $rate): ?float
    {
        return $rate === null ? null : round($rate, 1);
    }
}
