<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Url
{
    public static function temporary(?string $path, int $minutes = 10): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        if (! $bucket) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Throwable) {
            return null;
        }
    }
}
