<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriesSectionButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_section_renders_more_button_from_section_fields(): void
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Categories', 'ar' => 'Categories'],
            'slug' => 'categories-page',
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'categories',
            'title' => 'Categories',
            'subtitle' => 'Categories subtitle',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'button_text',
            'type' => 'string',
            'value' => [
                'en' => 'More Categories',
                'ar' => 'More Categories',
            ],
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'button_url',
            'type' => 'string',
            'value' => [
                'value' => '/courses',
            ],
            'sort_order' => 1,
        ]);

        $this->get('/categories-page')
            ->assertOk()
            ->assertSee('More Categories')
            ->assertSee('href="/courses"', false);
    }
}
