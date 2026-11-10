<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Qualifications\QualificationResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QualificationImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && QualificationResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/qualification-import-template.xlsx'),
            'qualification-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
