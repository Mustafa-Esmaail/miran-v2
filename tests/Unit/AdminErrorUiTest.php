<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdminErrorUiTest extends TestCase
{
    public function test_admin_panel_registers_custom_error_handler_view(): void
    {
        $providerContents = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));

        $this->assertIsString($providerContents);
        $this->assertStringContainsString('PanelsRenderHook::BODY_END', $providerContents);
        $this->assertStringContainsString("view('filament.components.admin-error-handler')", $providerContents);
    }

    public function test_admin_error_handler_view_converts_livewire_failures_to_filament_notifications(): void
    {
        $viewContents = file_get_contents(resource_path('views/filament/components/admin-error-handler.blade.php'));

        $this->assertIsString($viewContents);
        $this->assertStringContainsString('<div class="hidden" aria-hidden="true">', $viewContents);
        $this->assertStringContainsString("document.addEventListener('livewire:init'", $viewContents);
        $this->assertStringContainsString("Livewire.hook('request'", $viewContents);
        $this->assertStringContainsString('preventDefault()', $viewContents);
        $this->assertStringContainsString('new FilamentNotification()', $viewContents);
        $this->assertStringContainsString("__('admin.errors.reference')", $viewContents);
    }
}
