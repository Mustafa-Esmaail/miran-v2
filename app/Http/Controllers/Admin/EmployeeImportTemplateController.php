<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && EmployeeResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/employee-import-template.xlsx'),
            'employee-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
