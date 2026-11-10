<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Exports\PaymentsExport;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\Settings\SettingsService;
use App\Support\Exports\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

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
            'Order No',
            'Provider',
            'Reference',
            'Status',
            'Amount',
            'Created At',
        ];

        $rows = (clone $this->getFilteredTableQuery())
            ->reorder('id')
            ->lazy()
            ->map(function (Payment $payment): array {
                return [
                    $payment->order?->order_no ?? '',
                    $payment->provider,
                    $payment->provider_reference,
                    $payment->status?->value ?? (string) $payment->status,
                    $payment->amount,
                    $payment->created_at?->format('Y-m-d H:i'),
                ];
            });

        return app(CsvExport::class)->stream($this->exportFilename('payments', 'csv'), $headers, $rows);
    }

    private function exportXlsx(): BinaryFileResponse
    {
        return Excel::download(new PaymentsExport($this->getFilteredTableQuery()), $this->exportFilename('payments', 'xlsx'));
    }

    private function exportPdf(): Response
    {
        $filename = $this->exportFilename('payments', 'pdf');
        $payments = (clone $this->getFilteredTableQuery())
            ->reorder('id')
            ->get();

        $logoUrl = app(SettingsService::class)->logoUrl();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
            ->loadView('exports.payments', [
                'logoUrl' => $logoUrl,
                'rows' => $payments,
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
