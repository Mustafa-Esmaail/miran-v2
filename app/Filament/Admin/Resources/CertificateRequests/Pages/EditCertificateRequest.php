<?php

namespace App\Filament\Admin\Resources\CertificateRequests\Pages;

use App\Filament\Admin\Resources\CertificateRequests\CertificateRequestResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditCertificateRequest extends EditRecord
{
    protected static string $resource = CertificateRequestResource::class;

    public function form(Schema $schema): Schema
    {
        return CertificateRequestResource::buildForm($schema, statusEditable: true);
    }
}
