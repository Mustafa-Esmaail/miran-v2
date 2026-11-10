<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseGroupImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && CourseGroupResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/course-group-import-template.xlsx'),
            'course-group-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
