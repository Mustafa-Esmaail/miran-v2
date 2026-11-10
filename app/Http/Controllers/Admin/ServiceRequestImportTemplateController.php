<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ServiceRequestImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceRequestImportTemplateController extends Controller
{
    public function __invoke(ServiceRequestImportService $service): StreamedResponse
    {
        abort_unless(auth('admin')->check(), 403);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Service Requests Import');

        $headers = $service->templateHeaders();
        $sample = $service->templateSampleRow();
        $validValues = $service->templateValidValuesRow();

        foreach ($headers as $col => $header) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
            $cell->setValue($header);

            $sheet->getStyleByColumnAndRow($col + 1, 1)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getCellByColumnAndRow($col + 1, 2)->setValue($sample[$col] ?? '');
            $sheet->getStyleByColumnAndRow($col + 1, 2)->applyFromArray([
                'font' => ['color' => ['rgb' => '374151']],
            ]);

            $sheet->getCellByColumnAndRow($col + 1, 3)->setValue($validValues[$col] ?? '');
            $sheet->getStyleByColumnAndRow($col + 1, 3)->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
            ]);

            $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(20);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'service-request-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
