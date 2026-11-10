<?php

namespace Tests\Feature;

use Tests\TestCase;

class PartnersAndFooterLayoutTest extends TestCase
{
    public function test_partners_section_uses_swiper_slider_markup(): void
    {
        $contents = file_get_contents(base_path('resources/views/sections/partners.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('class="swiper partners-swiper"', $contents);
        $this->assertStringContainsString('class="swiper-wrapper"', $contents);
        $this->assertStringContainsString('class="swiper-slide', $contents);
        $this->assertStringContainsString('loop: true', $contents);
        $this->assertStringContainsString('autoplay', $contents);
    }

    public function test_footer_map_area_stacks_as_rows_on_mobile(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/footer.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('flex flex-col gap-3 lg:flex-row', $contents);
        $this->assertStringContainsString('w-full text-sm text-[#242528] lg:max-w-[220px]', $contents);
        $this->assertStringContainsString('w-full lg:flex-1', $contents);
    }
}
