<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PolicyTextSectionTest extends TestCase
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

    public function test_policy_text_section_renders_body(): void
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Pricing Policy', 'ar' => 'Pricing Policy'],
            'slug' => 'pricing-policy',
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'policy_text',
            'title' => 'Pricing Policy',
            'subtitle' => 'Understand our pricing rules',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'body',
            'type' => 'string',
            'value' => [
                'en' => '<p>All prices are shown in SAR.</p>',
                'ar' => '<p>All prices are shown in SAR.</p>',
            ],
            'sort_order' => 0,
        ]);

        $this->get('/pricing-policy')
            ->assertOk()
            ->assertSee('Pricing Policy')
            ->assertSee('All prices are shown in SAR.', false);
    }

    public function test_policy_text_section_resolves_s3_urls_for_images_and_attachments(): void
    {
        config(['filesystems.disks.s3.bucket' => 'test-bucket']);
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path): string => 'https://cdn.example.test/'.$path
        );

        $page = Page::query()->create([
            'title' => ['en' => 'Docs', 'ar' => 'Docs'],
            'slug' => 'docs-policy',
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'policy_text',
            'title' => 'Docs',
            'subtitle' => '',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'body',
            'type' => 'string',
            'value' => [
                'en' => '<p><img src="course-intros/policy.png"><a href="course-intros/policy.pdf">Download</a></p>',
                'ar' => '<p><img src="course-intros/policy.png"><a href="course-intros/policy.pdf">Download</a></p>',
            ],
            'sort_order' => 0,
        ]);

        $this->get('/docs-policy')
            ->assertOk()
            ->assertSee('src="https://cdn.example.test/course-intros/policy.png"', false)
            ->assertSee('href="https://cdn.example.test/course-intros/policy.pdf"', false);
    }

    public function test_policy_text_section_resolves_image_data_id_to_src(): void
    {
        config(['filesystems.disks.s3.bucket' => 'test-bucket']);
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path): string => 'https://cdn.example.test/'.$path
        );

        $page = Page::query()->create([
            'title' => ['en' => 'Docs', 'ar' => 'Docs'],
            'slug' => 'docs-policy-image-data-id',
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'policy_text',
            'title' => 'Docs',
            'subtitle' => '',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'body',
            'type' => 'string',
            'value' => [
                'en' => '<p><img data-id="course-intros/policy.png"></p>',
                'ar' => '<p><img data-id="course-intros/policy.png"></p>',
            ],
            'sort_order' => 0,
        ]);

        $this->get('/docs-policy-image-data-id')
            ->assertOk()
            ->assertSee('src="https://cdn.example.test/course-intros/policy.png"', false);
    }
}
