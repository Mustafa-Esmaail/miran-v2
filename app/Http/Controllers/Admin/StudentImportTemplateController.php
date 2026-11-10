<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Students\StudentResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && StudentResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/student-import-template.xlsx'),
            'student-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
