<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use Database\Seeders\LandingWebpImagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingWebpImagesSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    public function test_it_switches_bundled_pngs_to_webp_and_uploads_them(): void
    {
        $page = $this->createPage('excel', 'landing-excel');
        $hero = $this->createSection($page, 'landing_course_hero', 0);
        $hero->setField('heading', ['ar' => 'عنوان', 'en' => 'Heading'], 'string', 1);
        $hero->setField('badges', ['value' => [
            ['image' => 'sections/landing-excel/license-tvtc.png', 'name_ar' => 'المؤسسة'],
        ]], 'json', 0);
        $clients = $this->createSection($page, 'landing_v2_clients', 1);
        $clients->setField('logos', ['value' => [
            'sections/landing-v2/aramco.png',
            'sections/uploads/custom-logo.png',
        ]], 'json', 0);
        $benefits = $this->createSection($page, 'landing_course_benefits', 2);
        $benefits->setField('tools', ['value' => [
            ['image' => 'sections/landing-excel/icon-01.png', 'text_ar' => 'أداة'],
        ]], 'json', 2);
        $benefits->setField('extras', ['value' => [
            ['image' => 'sections/landing-excel/icon-06.png', 'text_ar' => 'شهادة'],
        ]], 'json', 4);

        $this->seed(LandingWebpImagesSeeder::class);

        $this->assertSame('sections/landing-excel/license-tvtc.webp', $hero->fresh()->fieldValue('badges')[0]['image']);
        $this->assertSame('المؤسسة', $hero->fresh()->fieldValue('badges')[0]['name_ar']);
        $this->assertSame('عنوان', $hero->fresh()->fieldText('heading', 'ar'));
        $this->assertSame(
            ['sections/landing-v2/aramco.webp', 'sections/uploads/custom-logo.png'],
            $clients->fresh()->fieldValue('logos'),
        );
        $this->assertSame('sections/landing-excel/icon-01.webp', $benefits->fresh()->fieldValue('tools')[0]['image']);
        $this->assertSame('sections/landing-excel/icon-06.webp', $benefits->fresh()->fieldValue('extras')[0]['image']);

        Storage::disk('s3')->assertExists([
            'sections/landing-excel/license-tvtc.webp',
            'sections/landing-v2/aramco.webp',
            'sections/landing-excel/icon-01.webp',
            'sections/landing-excel/icon-06.webp',
        ]);
    }

    public function test_it_converts_all_four_landing_pages_and_leaves_other_pages_untouched(): void
    {
        $fields = [];
        foreach (['excel' => 'landing-excel', 'kpis-online' => 'landing-kpi', 'Power-BI' => 'landing-powerbi', 'pmp-online-v3' => 'landing-v2', 'pmp-online-v2' => 'landing-v2'] as $slug => $template) {
            $section = $this->createSection($this->createPage($slug, $template), 'landing_v2_clients', 0);
            $section->setField('logos', ['value' => ['sections/landing-v2/mod.png']], 'json', 0);
            $fields[$slug] = $section;
        }

        $this->seed(LandingWebpImagesSeeder::class);

        foreach (['excel', 'kpis-online', 'Power-BI', 'pmp-online-v3'] as $slug) {
            $this->assertSame(['sections/landing-v2/mod.webp'], $fields[$slug]->fresh()->fieldValue('logos'));
        }
        $this->assertSame(['sections/landing-v2/mod.png'], $fields['pmp-online-v2']->fresh()->fieldValue('logos'));
    }

    public function test_it_keeps_pngs_without_a_webp_copy_and_is_safe_to_run_twice(): void
    {
        $page = $this->createPage('kpis-online', 'landing-kpi');
        $clients = $this->createSection($page, 'landing_v2_clients', 0);
        $clients->setField('logos', ['value' => [
            'sections/landing-v2/hadaf.png',
            'sections/landing-v2/tga.png',
        ]], 'json', 0);

        $this->seed(LandingWebpImagesSeeder::class);
        $this->seed(LandingWebpImagesSeeder::class);

        $this->assertSame(
            ['sections/landing-v2/hadaf.png', 'sections/landing-v2/tga.webp'],
            $clients->fresh()->fieldValue('logos'),
        );
        Storage::disk('s3')->assertMissing('sections/landing-v2/hadaf.webp');
    }

    public function test_the_bundled_webp_copies_exist_for_every_seeded_image(): void
    {
        $logos = ['aramco', 'makkah-chamber', 'moi', 'sami', 'takamul', 'momrah', 'mod', 'mutabaqa', 'tga', 'saudi-lime', 'mark'];
        foreach ($logos as $name) {
            $this->assertFileExists(public_path("img/landing-v2/{$name}.webp"));
        }

        foreach (['landing-excel', 'landing-kpi', 'landing-powerbi'] as $directory) {
            foreach ([...array_map(fn (int $i) => sprintf('icon-%02d', $i), range(1, 10)), 'license-tvtc', 'license-nelc'] as $name) {
                $this->assertFileExists(public_path("img/{$directory}/{$name}.webp"));
            }
        }
    }

    private function createPage(string $slug, string $template): Page
    {
        return Page::query()->create([
            'title' => ['en' => 'Landing', 'ar' => 'Landing'],
            'slug' => $slug,
            'body' => ['en' => '', 'ar' => ''],
            'template' => $template,
            'is_published' => true,
            'is_active' => true,
        ]);
    }

    private function createSection(Page $page, string $type, int $sortOrder): Section
    {
        return Section::query()->create([
            'page_id' => $page->id,
            'type' => $type,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
