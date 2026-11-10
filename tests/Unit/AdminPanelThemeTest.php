<?php

namespace Tests\Unit;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Tests\TestCase;

class AdminPanelThemeTest extends TestCase
{
    private function themeCss(): string
    {
        return (string) file_get_contents(resource_path('css/filament/admin/theme.css'));
    }

    public function test_panel_uses_the_miran_brand_palettes(): void
    {
        $colors = Filament::getPanel('admin')->getColors();

        $this->assertSame('#9A7259', $colors['primary'][600]);
        $this->assertSame('#C69C7A', $colors['primary'][400]);
        $this->assertSame('#3F7A57', $colors['success'][600]);
        $this->assertSame('#B4483C', $colors['danger'][600]);
        $this->assertSame('#A8721E', $colors['warning'][600]);
        $this->assertSame(Color::hex('#7B2BC9'), $colors['info']);
        $this->assertSame('#EEF0EF', $colors['gray'][100]);
        $this->assertSame('#2B3936', $colors['gray'][800]);
        $this->assertSame('#141A19', $colors['gray'][950]);

        foreach (['primary', 'success', 'danger', 'warning', 'gray'] as $palette) {
            $this->assertSame(
                [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950],
                array_keys($colors[$palette]),
                "{$palette} must define all 11 shades explicitly; Color::hex() would replace its lightness and chroma.",
            );
        }
    }

    public function test_light_mode_primary_buttons_are_copper_with_white_text(): void
    {
        $css = $this->themeCss();

        $this->assertMatchesRegularExpression(
            '/html:not\(\.dark\) \.fi-btn\.fi-color-primary:not\(\.fi-outlined\):not\(label\) \{\s*background-color: var\(--primary-600\);\s*color: var\(--mr-on-dark\);/',
            $css,
        );
        $this->assertStringContainsString('background-color: var(--primary-700);', $css);
    }

    public function test_invalid_fields_keep_filaments_danger_ring(): void
    {
        $css = $this->themeCss();

        $this->assertStringContainsString('.fi-input-wrp:not(.fi-invalid) {', $css);
        $this->assertStringContainsString('.fi-input-wrp:focus-within:not(.fi-disabled):not(.fi-invalid) {', $css);
    }

    public function test_theme_does_not_redefine_filament_palette_variables(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/--(primary|gray|success|danger|warning|info)-\d+\s*:/',
            $this->themeCss(),
        );
    }

    public function test_theme_uses_direction_neutral_properties_only(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/(?<![\w-])(margin|padding|border)-(left|right)\s*:|(?<![\w-])(left|right)\s*:/',
            $this->themeCss(),
        );
    }

    public function test_theme_loads_the_miran_fonts(): void
    {
        $css = $this->themeCss();

        $this->assertSame('IBM Plex Sans Arabic', Filament::getPanel('admin')->getFontFamily());
        $this->assertStringContainsString('family=Alexandria', $css);
        $this->assertStringNotContainsString('Tajawal', $css);
    }
}
