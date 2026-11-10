<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    public function test_all_notifications_use_database_channel(): void
    {
        $files = File::allFiles(app_path('Notifications'));

        foreach ($files as $file) {
            $class = 'App\\Notifications\\'.$file->getFilenameWithoutExtension();
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $instance = $reflection->newInstanceWithoutConstructor();
            $channels = $instance->via(new class {});

            $this->assertSame(['database'], $channels, $class);
        }
    }
}
