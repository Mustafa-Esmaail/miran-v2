<?php

namespace Tests\Feature;

use Tests\TestCase;

class FooterAndMapSectionTest extends TestCase
{
    public function test_footer_contains_four_column_layout_and_bottom_bar(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/footer.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('lg:grid-cols-4', $contents);
        $this->assertStringContainsString('@foreach ($footerCertificationCards as $card)', $contents);
        $this->assertStringContainsString('@foreach ($paymentMethodLogos as $method)', $contents);
        $this->assertStringContainsString('&copy; {{ $year }} Miran.', $contents);
        $this->assertStringContainsString("__('web.footer.follow_us')", $contents);
    }

    public function test_map_section_type_is_available_in_section_resources(): void
    {
        $sectionResourceContents = file_get_contents(base_path('app/Filament/Admin/Resources/Sections/Schemas/SectionForm.php'));
        $relationManagerContents = file_get_contents(base_path('app/Filament/Admin/Resources/Page/RelationManagers/SectionsRelationManager.php'));
        $mapSectionViewContents = file_get_contents(base_path('resources/views/sections/map.blade.php'));

        $this->assertNotFalse($sectionResourceContents);
        $this->assertNotFalse($relationManagerContents);
        $this->assertNotFalse($mapSectionViewContents);

        $this->assertStringContainsString("'map' => __('admin.options.map')", $sectionResourceContents);
        $this->assertStringContainsString('protected static function mapFields(): array', $sectionResourceContents);
        $this->assertStringContainsString("Textarea::make('content.address_ar')", $sectionResourceContents);
        $this->assertStringContainsString("TextInput::make('content.map_url')", $sectionResourceContents);
        $this->assertStringContainsString("'map' => __('admin.options.map')", $relationManagerContents);
        $this->assertStringContainsString('protected function mapFields(): array', $relationManagerContents);
        $this->assertStringContainsString("Textarea::make('content.address_ar')", $relationManagerContents);
        $this->assertStringContainsString("TextInput::make('content.map_url')", $relationManagerContents);
        $this->assertStringContainsString('<section class="section-shell">', $mapSectionViewContents);
        $this->assertStringContainsString("\$getText('address'", $mapSectionViewContents);
        $this->assertStringContainsString('class="h-[340px] w-full', $mapSectionViewContents);
    }

    public function test_footer_dynamic_sources_are_configured_from_settings_and_menu_locations(): void
    {
        $settingsPageContents = file_get_contents(base_path('app/Filament/Pages/Settings.php'));
        $settingsServiceContents = file_get_contents(base_path('app/Services/Settings/SettingsService.php'));
        $menuResourceContents = file_get_contents(base_path('app/Filament/Admin/Resources/Menus/Schemas/MenuForm.php'));
        $footerComponentContents = file_get_contents(base_path('app/View/Components/Site/Footer.php'));
        $layoutComposerContents = file_get_contents(base_path('app/View/Composers/AppLayoutComposer.php'));

        $this->assertNotFalse($settingsPageContents);
        $this->assertNotFalse($settingsServiceContents);
        $this->assertNotFalse($menuResourceContents);
        $this->assertNotFalse($footerComponentContents);
        $this->assertNotFalse($layoutComposerContents);

        $this->assertStringContainsString("Repeater::make('footer_certification_cards')", $settingsPageContents);
        $this->assertStringContainsString("'footer_certification_cards' => ['key' => 'footer.certification_cards'", $settingsServiceContents);
        $this->assertStringContainsString("'footer_policies' => __('admin.options.footer_policies')", $menuResourceContents);
        $this->assertStringContainsString("getActiveItemsForLocation('footer_policies')", $footerComponentContents);
        $this->assertStringContainsString('$this->settingsService->paymentMethodLogos()', $layoutComposerContents);
        $this->assertStringContainsString("\$this->settingsService->getValue('footer.certification_cards'", $layoutComposerContents);
    }
}
