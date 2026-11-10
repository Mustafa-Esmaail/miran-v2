<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && UserResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/user-import-template.xlsx'),
            'user-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
