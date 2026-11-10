<?php

namespace Tests\Unit;

use App\Support\S3Url;
use Tests\TestCase;

class S3UrlTest extends TestCase
{
    public function test_it_returns_null_when_s3_bucket_missing(): void
    {
        config(['filesystems.disks.s3.bucket' => null]);

        $this->assertNull(S3Url::temporary('path/to/file.pdf'));
    }

    public function test_it_returns_http_url_as_is(): void
    {
        $url = 'https://example.com/file.pdf';

        $this->assertSame($url, S3Url::temporary($url));
    }
}
