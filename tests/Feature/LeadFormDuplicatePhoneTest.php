<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Livewire\Home\LeadForm;
use App\Livewire\Home\ServiceRequestForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormDuplicatePhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_submission_same_day_same_phone_is_rejected(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'First')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 1);

        Livewire::test(LeadForm::class)
            ->set('name', 'Second')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('phone')
            ->assertSee(__('web.home.lead_duplicate_today'));

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_duplicate_check_normalizes_phone_format_differences(): void
    {
        $course = $this->createPublishedCourse();

        // Same real number, submitted with a different country-code prefix format.
        Livewire::test(LeadForm::class)
            ->set('name', 'First')
            ->set('countryCode', '+966')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        Livewire::test(ServiceRequestForm::class)
            ->set('name', 'Second')
            ->set('phone', '0555555555')
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('phone');

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_different_phone_numbers_are_not_blocked(): void
    {
        // Distinct IPs isolate this from the same-IP 12h cooldown, so it purely tests the phone guard.
        // Livewire::test() always dispatches sub-requests from a fixed 127.0.0.1, so the service is
        // exercised directly here to control the IP per submission.
        $service = app(ServiceRequestService::class);

        $service->createFromLeadForm([
            'name' => 'First',
            'phone' => '0555555555',
            'ip_address' => '10.0.0.1',
        ], ServiceRequestSource::Lead);

        $service->createFromLeadForm([
            'name' => 'Second',
            'phone' => '0555555556',
            'ip_address' => '10.0.0.2',
        ], ServiceRequestSource::Lead);

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_same_phone_previous_day_is_allowed(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequest::factory()->create([
            'phone' => '0555555555',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Today')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_same_phone_within_the_last_12_hours_is_rejected(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'created_at' => now()->subHours(11),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Second')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('phone');

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_same_phone_over_12_hours_ago_is_allowed(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'created_at' => now()->subHours(13),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Second')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_duplicate_error_uses_formdata_phone_key_for_dynamic_fields_layout(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'First')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        Livewire::test(LeadForm::class)
            ->set('dynamicFields', [['name' => 'phone', 'type' => 'tel', 'required' => true]])
            ->set('formData', ['phone' => '0555555555'])
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('formData.phone');

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_normalize_phone_collapses_formats_to_the_same_international_number(): void
    {
        $this->assertSame('966555555555', ServiceRequestService::normalizePhone('0555555555'));
        $this->assertSame('966555555555', ServiceRequestService::normalizePhone('+966555555555'));
        $this->assertSame('966555555555', ServiceRequestService::normalizePhone('+9660555555555'));
        $this->assertSame('966555555555', ServiceRequestService::normalizePhone('966 555 555 555'));
        $this->assertSame('', ServiceRequestService::normalizePhone(''));
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
