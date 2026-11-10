<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroSectionSlidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_slide_renders_its_own_button_text_and_link(): void
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Home', 'ar' => 'Home'],
            'slug' => 'hero-slide-page',
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'hero',
            'title' => 'Hero',
            'subtitle' => 'Hero subtitle',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'slides',
            'type' => 'json',
            'value' => [
                'value' => [
                    [
                        'heading_en' => 'Slide heading',
                        'subheading_en' => 'Slide subheading',
                        'button_text_en' => 'Slide CTA',
                        'button_url' => '/courses',
                    ],
                ],
            ],
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'primary_button_text',
            'type' => 'string',
            'value' => [
                'en' => 'Global CTA',
                'ar' => 'Global CTA',
            ],
            'sort_order' => 1,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'primary_button_url',
            'type' => 'string',
            'value' => [
                'value' => '/global',
            ],
            'sort_order' => 2,
        ]);

        $this->get('/hero-slide-page')
            ->assertOk()
            ->assertSee('Slide CTA')
            ->assertSee('href="/courses"', false)
            ->assertDontSee('href="/global"', false);
    }
}
