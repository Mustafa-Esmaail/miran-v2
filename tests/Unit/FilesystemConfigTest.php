<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilesystemConfigTest extends TestCase
{
    public function test_default_filesystem_disk_is_s3(): void
    {
        $this->assertSame('s3', config('filesystems.default'));
    }

    public function test_s3_disk_has_public_visibility(): void
    {
        $this->assertSame('public', config('filesystems.disks.s3.visibility'));
    }
}
