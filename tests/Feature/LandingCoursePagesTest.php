<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Page;
use App\Models\Section;
use App\Models\ServiceRequestType;
use Database\Seeders\LandingExcelPageSeeder;
use Database\Seeders\LandingKpiPageSeeder;
use Database\Seeders\LandingPowerBiPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LandingCoursePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function templates(): array
    {
        return [
            'excel' => ['landing-excel'],
            'kpi' => ['landing-kpi'],
            'powerbi' => ['landing-powerbi'],
        ];
    }

    /**
     * @return array<string, array{class-string, string, string, string}>
     */
    public static function seeders(): array
    {
        return [
            'excel' => [LandingExcelPageSeeder::class, 'excel', 'advanced-excel', 'landing-excel'],
            'kpi' => [LandingKpiPageSeeder::class, 'kpis-online', 'kpis-online', 'landing-kpi'],
            'powerbi' => [LandingPowerBiPageSeeder::class, 'Power-BI', 'power-bi', 'landing-powerbi'],
        ];
    }

    #[DataProvider('templates')]
    public function test_template_renders_the_course_hero_and_benefits(string $template): void
    {
        $course = $this->createCourse('course-'.Str::lower(Str::random(6)));
        $start = now()->addDays(5)->setTime(18, 0);
        $this->createGroup($course, $start);
        $page = $this->createPage($template, $course);
        $this->createHero($page);
        $this->createBenefits($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('lv-course', false)
            ->assertSee('<em>عشرة آلاف صف؟</em>', false)
            ->assertSee('ترخيص 2241119295411812')
            ->assertSee('<s>999</s>', false)
            ->assertSee('<em>266.33 ريال</em>', false)
            ->assertSee('class="tamara"', false)
            ->assertSee($start->locale(app()->getLocale())->translatedFormat('l j F'))
            ->assertSee('20 ساعة')
            ->assertSee('class="kt wide"', false)
            ->assertSee('ومعها أيضًا')
            ->assertSee('تجد أي معلومة في ثوانٍ')
            ->assertSee(__('web.landing_course.story_after'))
            ->assertSee(url('/privacy-policy'), false)
            ->assertSee('lv2-phone', false);
    }

    #[DataProvider('templates')]
    public function test_template_exposes_section_anchors_for_ad_sitelinks(string $template): void
    {
        $page = $this->createPage($template, null);
        $this->createHero($page);
        $clients = $this->createSection($page, 'landing_v2_clients', 1);
        $clients->setField('logos', ['value' => ['https://example.com/logo.png']], 'json');
        $this->createBenefits($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('id="form"', false)
            ->assertSee('id="clients"', false)
            ->assertSee('id="benefits"', false)
            ->assertSee('id="outcomes"', false);
    }

    #[DataProvider('templates')]
    public function test_template_renders_without_the_page_fade_in(string $template): void
    {
        $page = $this->createPage($template, null);
        $this->createHero($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('<main class="py-0">', false)
            ->assertDontSee('page-enter', false);
    }

    #[DataProvider('templates')]
    public function test_template_loads_swiper_only_once(string $template): void
    {
        $page = $this->createPage($template, null);
        $this->createHero($page);
        $clients = $this->createSection($page, 'landing_v2_clients', 1);
        $clients->setField('logos', ['value' => ['https://example.com/logo.webp']], 'json');
        $this->createBenefits($page);

        $this->withoutVite();

        $html = $this->get('/'.$page->slug)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'swiper-bundle.min.js'));
        $this->assertSame(1, substr_count($html, 'swiper-bundle.min.css'));
    }

    public function test_next_batch_ignores_past_and_closed_groups(): void
    {
        $course = $this->createCourse('kpis-online');
        $this->createGroup($course, now()->subDays(3));
        $this->createGroup($course, now()->addDays(2), 'closed');
        $page = $this->createPage('landing-kpi', $course);
        $this->createHero($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee(__('web.landing_course.next_batch_soon'));
    }

    public function test_next_batch_shows_today_for_a_group_starting_today(): void
    {
        $course = $this->createCourse('power-bi');
        $this->createGroup($course, now()->endOfDay()->subMinute());
        $page = $this->createPage('landing-powerbi', $course);
        $this->createHero($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee(__('web.landing_v2.next_batch_today'))
            ->assertDontSee(__('web.landing_course.next_batch_soon'));
    }

    public function test_page_without_a_course_still_renders(): void
    {
        $page = $this->createPage('landing-excel', null);
        $this->createHero($page);
        $this->createBenefits($page);

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee(__('web.landing_course.next_batch_soon'));
    }

    #[DataProvider('seeders')]
    public function test_seeder_creates_the_linked_landing_page(string $seeder, string $pageSlug, string $courseSlug, string $template): void
    {
        Storage::fake('s3');
        $course = $this->createCourse($courseSlug);
        $type = ServiceRequestType::query()->firstOrCreate(
            ['slug' => 'tlbat'],
            ['name' => ['ar' => 'طلبات'], 'is_active' => true, 'sort_order' => 1],
        );

        $this->seed($seeder);

        $page = Page::query()->where('slug', $pageSlug)->firstOrFail();
        $this->assertSame($template, $page->template);
        $this->assertSame(Course::class, $page->landable_type);
        $this->assertSame($course->id, $page->landable_id);
        $this->assertSame(
            ['landing_course_hero', 'landing_v2_clients', 'landing_course_benefits'],
            $page->sections()->pluck('type')->all(),
        );

        $hero = $page->sections()->where('type', 'landing_course_hero')->firstOrFail();
        $this->assertSame($type->id, (int) $hero->fieldValue('type_id'));

        $benefits = $page->sections()->where('type', 'landing_course_benefits')->firstOrFail();
        $this->assertCount(5, $benefits->fieldValue('tools'));
        $this->assertCount(6, $benefits->fieldValue('stories'));
        $this->assertSame("sections/{$template}/icon-01.webp", $benefits->fieldValue('tools')[0]['image']);
        Storage::disk('s3')->assertExists("sections/{$template}/icon-10.webp");

        $this->withoutVite();
        $this->get('/'.$pageSlug)
            ->assertOk()
            ->assertSee('id="form"', false)
            ->assertSee('id="benefits"', false)
            ->assertSee('id="outcomes"', false);
    }

    public function test_seeder_leaves_an_existing_page_untouched(): void
    {
        Storage::fake('s3');

        $this->seed(LandingExcelPageSeeder::class);
        $this->seed(LandingExcelPageSeeder::class);

        $this->assertSame(3, Section::query()->count());
    }

    private function createCourse(string $slug): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'category'],
            ['name' => ['en' => 'Category'], 'is_active' => true],
        );

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course', 'ar' => 'دورة'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);
    }

    private function createGroup(Course $course, \DateTimeInterface $startAt, string $status = 'open'): CourseGroup
    {
        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => $status,
            'start_at' => $startAt,
        ]);
    }

    private function createPage(string $template, ?Course $course): Page
    {
        return Page::query()->create([
            'title' => ['en' => 'Landing', 'ar' => 'Landing'],
            'slug' => 'landing-course-'.Str::lower(Str::random(8)),
            'body' => ['en' => '', 'ar' => ''],
            'template' => $template,
            'landable_type' => $course ? Course::class : null,
            'landable_id' => $course?->id,
            'is_published' => true,
            'is_active' => true,
        ]);
    }

    private function createHero(Page $page): Section
    {
        $hero = $this->createSection($page, 'landing_course_hero', 0);
        $hero->setField('badges', ['value' => [
            ['name_ar' => 'المؤسسة العامة للتدريب التقني والمهني', 'detail_ar' => 'ترخيص 2241119295411812'],
        ]], 'json');
        $hero->setField('heading', ['ar' => 'طلب مديرك تقريرًا، وأمامك *عشرة آلاف صف؟*', 'en' => 'Ten *thousand rows?*'], 'string');
        $hero->setField('price', ['ar' => '899', 'en' => '899'], 'string');
        $hero->setField('old_price', ['ar' => '999', 'en' => '999'], 'string');
        $hero->setField('installment', ['ar' => 'أو 3 دفعات × *266.33 ريال* شهريًا مع', 'en' => 'Or 3 × *SAR 266.33* with'], 'string');
        $hero->setField('installment_badge', ['ar' => 'تمارا', 'en' => 'Tamara'], 'string');

        return $hero;
    }

    private function createBenefits(Page $page): Section
    {
        $benefits = $this->createSection($page, 'landing_course_benefits', 1);
        $benefits->setField('facts', ['value' => [
            ['value_ar' => '20 ساعة', 'label_ar' => 'تدريب مباشر عن بعد'],
        ]], 'json');
        $benefits->setField('title', ['ar' => 'خمس أدوات *تأخذها لعملك*'], 'string');
        $benefits->setField('tools', ['value' => [
            ['text_ar' => 'أداة 1'],
            ['text_ar' => 'أداة 2'],
            ['text_ar' => 'أداة 3'],
        ]], 'json');
        $benefits->setField('extras_title', ['ar' => 'ومعها أيضًا'], 'string');
        $benefits->setField('extras', ['value' => [['text_ar' => 'شهادة معتمدة']]], 'json');
        $benefits->setField('stories_title', ['ar' => 'ماذا يتغيّر في عملك بعد الدورة؟'], 'string');
        $benefits->setField('stories', ['value' => [
            ['title_ar' => 'تجد أي معلومة في ثوانٍ', 'now_ar' => 'يدويًا', 'after_ar' => 'بمعادلة واحدة'],
        ]], 'json');

        return $benefits;
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
