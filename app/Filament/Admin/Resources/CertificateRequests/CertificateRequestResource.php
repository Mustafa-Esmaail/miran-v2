<?php

namespace App\Filament\Admin\Resources\CertificateRequests;

use App\Filament\Admin\Resources\CertificateRequests\Schemas\CertificateRequestForm;
use App\Filament\Admin\Resources\CertificateRequests\Tables\CertificateRequestsTable;
use App\Models\CertificateRequest;
use App\Models\Enrollment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateRequestResource extends Resource
{
    protected static ?string $model = CertificateRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.certificate_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.certificate_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.certificate_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_certificates_group');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return static::buildForm($schema, statusEditable: false);
    }

    public static function buildForm(Schema $schema, bool $statusEditable): Schema
    {
        return CertificateRequestForm::configure($schema, statusEditable: $statusEditable);
    }

    public static function table(Table $table): Table
    {
        return CertificateRequestsTable::configure($table);
    }

    public static function exportToXlsx(?Builder $query = null): StreamedResponse
    {
        $records = (clone ($query ?? Enrollment::query()->leftJoin('certificate_requests', function ($join): void {
            $join->on('certificate_requests.user_id', '=', 'enrollments.user_id')
                ->on('certificate_requests.course_group_id', '=', 'enrollments.course_group_id');
        })))
            ->reorder('enrollments.created_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'رقم الهوية',
            'الاسم الاول',
            'الاسم الثاني',
            'الاسم الثالث',
            'الاسم الاخير',
            'تاريخ الميلاد',
            'النوع',
            'الجنسية',
            'المؤهل',
            'البريد الالكتروني',
            'رقم الجوال',
            'الاسم الاول باللغة الإنجليزية',
            'الاسم الثاني باللغة الإنجليزية',
            'الاسم الثالث باللغة الإنجليزية',
            'الاسم الاخير باللغة الإنجليزية',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        foreach ($records as $i => $record) {
            $row = $i + 2;
            $sheet->setCellValue([1,  $row], $record->cr_id_number ?? '');
            $sheet->setCellValue([2,  $row], $record->cr_first_name_ar ?? '');
            $sheet->setCellValue([3,  $row], $record->cr_second_name_ar ?? '');
            $sheet->setCellValue([4,  $row], $record->cr_third_name_ar ?? '');
            $sheet->setCellValue([5,  $row], $record->cr_last_name_ar ?? '');
            $sheet->setCellValue([6,  $row], $record->cr_birth_date ? \Illuminate\Support\Carbon::parse($record->cr_birth_date)->format('m/d/Y') : '');
            $sheet->setCellValue([7,  $row], $record->cr_gender === 'male' ? 'ذكر' : ($record->cr_gender === 'female' ? 'انثى' : ''));
            $sheet->setCellValue([8,  $row], $record->cr_nationality ?? '');
            $sheet->setCellValue([9,  $row], $record->cr_qualification ?? '');
            $sheet->setCellValue([10, $row], $record->cr_email ?? '');
            $sheet->setCellValue([11, $row], $record->cr_phone ?? '');
            $sheet->setCellValue([12, $row], $record->cr_first_name_en ?? '');
            $sheet->setCellValue([13, $row], $record->cr_second_name_en ?? '');
            $sheet->setCellValue([14, $row], $record->cr_third_name_en ?? '');
            $sheet->setCellValue([15, $row], $record->cr_last_name_en ?? '');
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'certificate-requests-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'courseGroup']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateRequests::route('/'),
            'edit' => Pages\EditCertificateRequest::route('/{record}/edit'),
            'view' => Pages\ViewCertificateRequest::route('/{record}'),
        ];
    }
}
