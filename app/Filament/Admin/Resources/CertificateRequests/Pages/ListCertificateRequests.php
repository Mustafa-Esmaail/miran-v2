<?php

namespace App\Filament\Admin\Resources\CertificateRequests\Pages;

use App\Filament\Admin\Resources\CertificateRequests\CertificateRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificateRequests extends ListRecords
{
    protected static string $resource = CertificateRequestResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
