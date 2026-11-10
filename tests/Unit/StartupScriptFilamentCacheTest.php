<?php

namespace Tests\Unit;

use Tests\TestCase;

class StartupScriptFilamentCacheTest extends TestCase
{
    public function test_startup_script_refreshes_filament_component_cache(): void
    {
        $contents = file_get_contents(base_path('docker/startup.sh'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('php artisan filament:clear-cached-components --no-interaction', $contents);
        $this->assertStringContainsString('php artisan filament:cache-components --no-interaction', $contents);
    }
}
