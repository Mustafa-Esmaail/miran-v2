<?php

namespace Tests\Feature;

use App\Livewire\Home\LeadForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormTurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsService::class)->clearCache();
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->clearCache();

        parent::tearDown();
    }

    private function enableTurnstile(string $secretKey = 'test-secret'): void
    {
        Setting::query()->create([
            'key' => 'turnstile.enabled',
            'group' => 'turnstile',
            'type' => 'boolean',
            'value' => true,
            'is_translatable' => false,
            'sort_order' => 76,
        ]);

        Setting::query()->create([
            'key' => 'turnstile.site_key',
            'group' => 'turnstile',
            'type' => 'string',
            'value' => 'test-site-key',
            'is_translatable' => false,
            'sort_order' => 77,
        ]);

        Setting::query()->create([
            'key' => 'turnstile.secret_key',
            'group' => 'turnstile',
            'type' => 'string',
            'value' => $secretKey,
            'is_translatable' => false,
            'sort_order' => 78,
        ]);

        app(SettingsService::class)->clearCache();
    }

    public function test_submission_succeeds_without_a_token_when_turnstile_is_not_configured(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'Tester')
            ->set('phone', '0555555601')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();
    }

    public function test_submission_is_blocked_when_turnstile_is_configured_and_token_is_missing(): void
    {
        $this->enableTurnstile();

        Livewire::test(LeadForm::class)
            ->set('name', 'Tester')
            ->set('phone', '0555555602')
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('turnstileToken');
    }

    public function test_submission_is_blocked_when_turnstile_verification_fails(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Tester')
            ->set('phone', '0555555603')
            ->set('turnstileToken', 'bad-token')
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('turnstileToken');
    }

    public function test_submission_succeeds_when_turnstile_verification_passes(): void
    {
        $course = $this->createPublishedCourse();

        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Tester')
            ->set('phone', '0555555604')
            ->set('courseId', $course->id)
            ->set('turnstileToken', 'good-token')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();
    }

    public function test_turnstile_widget_renders_only_when_enabled(): void
    {
        Livewire::test(LeadForm::class)
            ->assertDontSee('test-site-key', false);

        $this->enableTurnstile();

        Livewire::test(LeadForm::class)
            ->assertSee('test-site-key', false);
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
