<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Exports\OrdersExport;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Settings\SettingsService;
use App\Support\Exports\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

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
            'Customer',
            'Phone',
            'Email',
            'Status',
            'Subtotal',
            'Discount',
            'Total',
            'Currency',
            'Payment Method',
            'Coupon',
            'Employee Code',
            'Employee Name',
            'Notes',
            'Created At',
        ];

        $rows = (clone $this->getFilteredTableQuery())
            ->with(['referredByEmployee', 'payments', 'bankTransferReceipt'])
            ->reorder('id')
            ->lazy()
            ->map(function (Order $order): array {
                return [
                    $order->order_no,
                    $order->user?->name ?? '',
                    $order->customer_phone ?? $order->user?->phone ?? '',
                    $order->user?->email ?? '',
                    $order->status?->value ?? (string) $order->status,
                    $order->subtotal_amount,
                    $order->discount_amount,
                    $order->total_amount,
                    $order->currency,
                    $order->primary_payment_method ?? '',
                    $order->coupon?->code ?? '',
                    $order->referredByEmployee?->employee_code ?? '',
                    $order->referredByEmployee?->name ?? '',
                    $order->notes ?? '',
                    $order->created_at?->format('Y-m-d H:i'),
                ];
            });

        return app(CsvExport::class)->stream($this->exportFilename('orders', 'csv'), $headers, $rows);
    }

    private function exportXlsx(): BinaryFileResponse
    {
        return Excel::download(new OrdersExport($this->getFilteredTableQuery()), $this->exportFilename('orders', 'xlsx'));
    }

    private function exportPdf(): Response
    {
        $filename = $this->exportFilename('orders', 'pdf');
        $orders = (clone $this->getFilteredTableQuery())
            ->with(['referredByEmployee', 'payments', 'bankTransferReceipt'])
            ->reorder('id')
            ->get();

        $logoUrl = app(SettingsService::class)->logoUrl();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
            ->loadView('exports.orders', [
                'logoUrl' => $logoUrl,
                'rows' => $orders,
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
