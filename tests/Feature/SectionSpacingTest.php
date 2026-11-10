<?php

namespace Tests\Feature;

use Tests\TestCase;

class SectionSpacingTest extends TestCase
{
    public function test_section_spacing_uses_standard_padding(): void
    {
        $paths = [
            'resources/css/app.css',
            'resources/views/home.blade.php',
            'resources/views/sections/about_hero.blade.php',
            'resources/views/sections/about_partners.blade.php',
            'resources/views/sections/categories.blade.php',
            'resources/views/sections/contact_hero.blade.php',
            'resources/views/sections/cta_banner.blade.php',
            'resources/views/sections/hero.blade.php',
            'resources/views/sections/how_it_works.blade.php',
            'resources/views/sections/partners.blade.php',
            'resources/views/sections/policy_text.blade.php',
            'resources/views/sections/testimonials_strip.blade.php',
            'resources/views/sections/trainer_quotes.blade.php',
            'resources/views/sections/action_cards.blade.php',
            'resources/views/livewire/home/featured-courses.blade.php',
            'resources/views/livewire/home/hero-slider.blade.php',
            'resources/views/livewire/home/lead-form.blade.php',
            'resources/views/livewire/home/special-bundles.blade.php',
            'resources/views/livewire/home/testimonials-carousel.blade.php',
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents(base_path($path));

            $this->assertNotFalse($contents, "Expected to read {$path}.");
            $this->assertStringNotContainsString('py-12', $contents, "Unexpected py-12 found in {$path}.");
            $this->assertStringNotContainsString('py-16', $contents, "Unexpected py-16 found in {$path}.");
            $this->assertStringNotContainsString('py-20', $contents, "Unexpected py-20 found in {$path}.");
            $this->assertStringNotContainsString('py-24', $contents, "Unexpected py-24 found in {$path}.");
        }

        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertStringContainsString('@apply py-6', $css);
    }
}
