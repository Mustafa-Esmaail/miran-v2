<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilamentLogViewerPluginIntegrationTest extends TestCase
{
    public function test_admin_panel_registers_filament_log_viewer_plugin(): void
    {
        $contents = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;', $contents);
        $this->assertStringContainsString('FilamentLogViewerPlugin::make()', $contents);
        $this->assertStringContainsString("->navigationGroup(__('admin.nav_groups.settings'))", $contents);
    }

    public function test_admin_theme_includes_log_viewer_blade_sources(): void
    {
        $contents = file_get_contents(resource_path('css/filament/admin/theme.css'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("@source '../../../../vendor/gboquizosanchez/filament-log-viewer/resources/views/**/*.blade.php';", $contents);
    }

    public function test_log_viewer_driver_is_configured_as_raw_without_env_dependency(): void
    {
        $contents = file_get_contents(config_path('filament-log-viewer.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("'driver' => 'raw',", $contents);
    }
}
