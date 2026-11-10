<?php

namespace Tests\Unit;

use Tests\TestCase;

class CertificateRequestResourceEditableStatusTest extends TestCase
{
    public function test_view_page_reuses_the_read_only_form_with_status_disabled(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/CertificateRequests/CertificateRequestResource.php'));
        $formContents = file_get_contents(base_path('app/Filament/Admin/Resources/CertificateRequests/Schemas/CertificateRequestForm.php'));

        $this->assertIsString($contents);
        $this->assertIsString($formContents);
        $this->assertStringContainsString('return static::buildForm($schema, statusEditable: false);', $contents);
        $this->assertStringContainsString('$status = $status->disabled()->dehydrated(false);', $formContents);
    }

    public function test_edit_page_overrides_the_form_to_make_status_editable(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/CertificateRequests/Pages/EditCertificateRequest.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('CertificateRequestResource::buildForm($schema, statusEditable: true);', $contents);
    }
}
