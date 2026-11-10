<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Livewire\Landing\LeadFormV2;
use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use App\Models\Section;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_valid_phone_creates_a_landing_page_lead_and_redirects_to_thank_you(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadFormV2::class, ['lockedCourseId' => $course->id])
            ->set('phone', '55 123 4567')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('gtm-event')
            ->assertRedirect(route('thank-you'));

        $request = ServiceRequest::query()->firstOrFail();

        $this->assertSame('966551234567', $request->phone);
        $this->assertSame(ServiceRequestSource::LandingPage, $request->source);
        $this->assertSame($course->id, $request->course_id);
        $this->assertNull($request->name);
    }

    public function test_phone_input_mask_variants_are_normalized(): void
    {
        $this->assertSame('551234567', LeadFormV2::normalizeLocalPhone('55 123 4567'));
        $this->assertSame('551234567', LeadFormV2::normalizeLocalPhone('0551234567'));
        $this->assertSame('551234567', LeadFormV2::normalizeLocalPhone('+966 55 123 4567'));
        $this->assertSame('551234567', LeadFormV2::normalizeLocalPhone('٠٥٥١٢٣٤٥٦٧'));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        foreach (['', '45 123 4567', '55 123'] as $phone) {
            Livewire::test(LeadFormV2::class)
                ->set('phone', $phone)
                ->call('submit')
                ->assertHasErrors('phone')
                ->assertNoRedirect();
        }

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_empty_phone_fails_the_required_rule_and_unmasked_input_is_accepted(): void
    {
        Livewire::test(LeadFormV2::class)
            ->set('phone', '')
            ->call('submit')
            ->assertHasErrors(['phone' => 'required'])
            ->assertNoRedirect();

        Livewire::test(LeadFormV2::class)
            ->set('phone', '+966-055-123-4567')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('thank-you'));

        $this->assertSame('966551234567', ServiceRequest::query()->firstOrFail()->phone);
    }

    public function test_second_submission_same_day_is_rejected(): void
    {
        Livewire::test(LeadFormV2::class)
            ->set('phone', '551234567')
            ->call('submit')
            ->assertHasNoErrors();

        Livewire::test(LeadFormV2::class)
            ->set('phone', '0551234567')
            ->call('submit')
            ->assertHasErrors('phone')
            ->assertNoRedirect();

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_type_comes_from_the_hero_section(): void
    {
        $type = ServiceRequestType::query()->create([
            'name' => ['ar' => 'طلبات'],
            'slug' => 'landing_v2_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $section = $this->createPage()->sections()->create([
            'type' => 'landing_v2_hero',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $section->setField('type_id', ['value' => $type->id], 'number');

        Livewire::test(LeadFormV2::class, ['sectionId' => $section->id])
            ->set('phone', '551234567')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($type->id, ServiceRequest::query()->firstOrFail()->type_id);
    }

    public function test_utm_parameters_from_the_ad_click_are_saved_on_the_lead(): void
    {
        $this->get('/?utm_source=snapchat&utm_campaign=pmp-oct&utm_content=video-1');

        $this->assertSame(
            ['utm_source' => 'snapchat', 'utm_campaign' => 'pmp-oct', 'utm_content' => 'video-1'],
            session('utm'),
        );

        Livewire::test(LeadFormV2::class)
            ->set('phone', '551234567')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ServiceRequest::query()->firstOrFail();

        $this->assertSame('snapchat', $request->utm_source);
        $this->assertSame('pmp-oct', $request->utm_campaign);
        $this->assertSame('video-1', $request->utm_content);
        $this->assertNull($request->utm_medium);
    }

    public function test_requests_without_utm_keep_the_captured_set(): void
    {
        $this->get('/?utm_source=snapchat');
        $this->get('/');

        $this->assertSame(['utm_source' => 'snapchat'], session('utm'));

        $this->get('/?utm_source=google&utm_campaign=brand');

        $this->assertSame(['utm_source' => 'google', 'utm_campaign' => 'brand'], session('utm'));
    }

    public function test_landing_v2_page_renders_its_sections_and_form(): void
    {
        $page = $this->createPage();

        $hero = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'landing_v2_hero',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $hero->setField('heading', ['ar' => 'خبرتك تستاهل *PMP*', 'en' => 'Earn your *PMP*'], 'string');

        $benefits = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'landing_v2_benefits',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $benefits->setField('during_items', ['value' => [['text_ar' => 'بث مباشر', 'text_en' => 'Live']]], 'json');

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('<em>PMP</em>', false)
            ->assertSee('lv2-phone', false)
            ->assertSee('/img/sa-flag.png', false)
            ->assertSee(__('web.home.lead_phone_hint'));
    }

    private function createPage(): Page
    {
        return Page::query()->create([
            'title' => ['en' => 'Landing v2', 'ar' => 'Landing v2'],
            'slug' => 'landing-v2-'.Str::lower(Str::random(8)),
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'landing-v2',
            'is_published' => true,
            'is_active' => true,
        ]);
    }

    private function createPublishedCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);
    }
}
