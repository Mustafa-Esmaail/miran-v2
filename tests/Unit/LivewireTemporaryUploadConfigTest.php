<?php

namespace Tests\Unit;

use Tests\TestCase;

class LivewireTemporaryUploadConfigTest extends TestCase
{
    public function test_temporary_uploads_use_s3_disk(): void
    {
        $this->assertSame('s3', config('livewire.temporary_file_upload.disk'));
    }
}
