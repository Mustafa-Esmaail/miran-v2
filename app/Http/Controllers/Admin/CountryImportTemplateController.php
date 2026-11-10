<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Countries\CountryResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CountryImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && CountryResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/country-import-template.xlsx'),
            'country-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
