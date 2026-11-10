<?php

namespace Tests\Feature;

use Tests\TestCase;

class CtaCounterSectionTest extends TestCase
{
    public function test_cta_counter_section_type_is_available_in_section_resources(): void
    {
        $sectionFormContents = file_get_contents(base_path('app/Filament/Admin/Resources/Sections/Schemas/SectionForm.php'));
        $relationManagerContents = file_get_contents(base_path('app/Filament/Admin/Resources/Page/RelationManagers/SectionsRelationManager.php'));
        $viewContents = file_get_contents(base_path('resources/views/sections/cta_counter.blade.php'));

        $this->assertNotFalse($sectionFormContents);
        $this->assertNotFalse($relationManagerContents);
        $this->assertNotFalse($viewContents);

        $this->assertStringContainsString("'cta_counter' => __('admin.options.cta_counter')", $sectionFormContents);
        $this->assertStringContainsString('protected static function ctaCounterFields(): array', $sectionFormContents);
        $this->assertStringContainsString("DateTimePicker::make('content.target_date')", $sectionFormContents);
        $this->assertStringContainsString("FileUpload::make('content.background_image')", $sectionFormContents);

        $this->assertStringContainsString("'cta_counter' => __('admin.options.cta_counter')", $relationManagerContents);
        $this->assertStringContainsString('protected function ctaCounterFields(): array', $relationManagerContents);
        $this->assertStringContainsString("DateTimePicker::make('content.target_date')", $relationManagerContents);

        $this->assertStringContainsString('is_active', file_get_contents(base_path('app/Models/Section.php')));
    }

    public function test_cta_counter_view_stops_countdown_at_zero(): void
    {
        $viewContents = file_get_contents(base_path('resources/views/sections/cta_counter.blade.php'));

        $this->assertNotFalse($viewContents);
        $this->assertStringContainsString('x-data="ctaCounter(', $viewContents);
        $this->assertStringContainsString('if (diff <= 0)', $viewContents);
        $this->assertStringContainsString('clearInterval(this.timer);', $viewContents);
        $this->assertStringContainsString("__('web.common.countdown.days')", $viewContents);
        $this->assertStringContainsString("__('web.common.countdown.seconds')", $viewContents);
    }

    public function test_cta_counter_translations_exist(): void
    {
        $this->assertSame('CTA counter', __('admin.options.cta_counter', [], 'en'));
        $this->assertSame('Target date', __('admin.labels.target_date', [], 'en'));
        $this->assertSame('Seconds', __('web.common.countdown.seconds', [], 'en'));
        $this->assertSame('ثواني', __('web.common.countdown.seconds', [], 'ar'));
    }
}
