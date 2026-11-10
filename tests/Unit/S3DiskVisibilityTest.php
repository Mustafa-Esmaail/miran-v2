<?php

namespace Tests\Unit;

use Tests\TestCase;

class S3DiskVisibilityTest extends TestCase
{
    public function test_s3_disk_defaults_to_private_visibility(): void
    {
        $this->assertSame('private', config('filesystems.disks.s3.visibility'));
        $this->assertSame('s3', config('filesystems.default'));
    }
}
