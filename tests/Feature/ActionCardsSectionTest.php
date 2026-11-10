<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ActionCardsSectionTest extends TestCase
{
    use RefreshDatabase;

    private string $compiledPath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'null']);

        $this->compiledPath = base_path('tests/tmp/views');
        if (File::isDirectory($this->compiledPath)) {
            File::deleteDirectory($this->compiledPath);
        }
        if (! File::isDirectory($this->compiledPath)) {
            File::makeDirectory($this->compiledPath, 0755, true);
        }

        config(['view.compiled' => $this->compiledPath]);
        config(['app.locale' => 'en']);

        app()->forgetInstance('blade.compiler');
        app()->forgetInstance('view.engine.resolver');
        app()->forgetInstance('view');

        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        if (isset($this->compiledPath) && File::isDirectory($this->compiledPath)) {
            File::deleteDirectory($this->compiledPath);
        }

        parent::tearDown();
    }

    public function test_action_cards_section_renders_items_with_links_and_icons(): void
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Home', 'ar' => 'Home'],
            'slug' => 'home',
            'body' => ['en' => '', 'ar' => ''],
            'template' => null,
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'action_cards',
            'title' => 'Action cards',
            'subtitle' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'items',
            'type' => 'json',
            'value' => [
                'value' => [
                    [
                        'icon' => '<svg data-test="action-cards-icon" viewBox="0 0 24 24"></svg>',
                        'text_en' => 'Apply for Orders by your Number',
                        'text_ar' => 'Apply for Orders by your Number',
                        'url' => '/apply',
                    ],
                ],
            ],
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Apply for Orders by your Number')
            ->assertSee('data-test="action-cards-icon"', false)
            ->assertSee('/apply');
    }
}
