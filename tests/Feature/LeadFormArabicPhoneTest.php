<?php

namespace Tests\Feature;

use App\Livewire\Home\LeadForm;
use App\Livewire\Home\ServiceRequestForm;
use App\Models\Category;
use App\Models\Course;
use App\Services\ServiceRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormArabicPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_phone_converts_arabic_indic_digits(): void
    {
        $this->assertSame('966551234567', ServiceRequestService::normalizePhone('٠٥٥١٢٣٤٥٦٧'));
        $this->assertSame('966551234567', ServiceRequestService::normalizePhone('+٩٦٦ ٥٥ ١٢٣ ٤٥٦٧'));
    }

    public function test_normalize_local_phone_handles_arabic_digits_and_prefixes(): void
    {
        $this->assertSame('551234567', ServiceRequestService::normalizeLocalPhone('٠٥٥١٢٣٤٥٦٧'));
        $this->assertSame('551234567', ServiceRequestService::normalizeLocalPhone('+966 55 123 4567'));
        $this->assertSame('551234567', ServiceRequestService::normalizeLocalPhone('055-123-4567'));
        $this->assertSame('', ServiceRequestService::normalizeLocalPhone(''));
    }

    public function test_lead_form_saves_phone_typed_in_arabic_digits(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'Arabic Lead')
            ->set('phone', '٠٥٥١٢٣٤٥٦٧')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('service_requests', ['phone' => '966551234567']);
    }

    public function test_lead_form_dynamic_tel_field_saves_phone_typed_in_arabic_digits(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('dynamicFields', [['name' => 'phone', 'type' => 'tel', 'required' => true]])
            ->set('formData', ['phone' => '٥٥١٢٣٤٥٦٧'])
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('service_requests', ['phone' => '966551234567']);
    }

    public function test_lead_form_rejects_non_saudi_mobile_number(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'Bad Phone')
            ->set('phone', '123456')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertHasErrors(['phone' => 'regex'])
            ->assertSee(__('web.landing_v2.phone_invalid'));

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_lead_form_dynamic_tel_field_rejects_non_saudi_mobile_number(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('dynamicFields', [['name' => 'phone', 'type' => 'tel', 'required' => true]])
            ->set('formData', ['phone' => '٤٤٤٤٤٤'])
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertHasErrors(['formData.phone' => 'regex']);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_service_request_form_saves_phone_typed_in_arabic_digits(): void
    {
        Livewire::test(ServiceRequestForm::class)
            ->set('name', 'Arabic Request')
            ->set('phone', '٠٥٥١٢٣٤٥٦٧')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('service_requests', ['phone' => '966551234567']);
    }

    public function test_service_request_form_rejects_non_saudi_mobile_number(): void
    {
        Livewire::test(ServiceRequestForm::class)
            ->set('name', 'Bad Phone')
            ->set('phone', '123456')
            ->call('submit')
            ->assertHasErrors(['phone' => 'regex']);

        $this->assertDatabaseCount('service_requests', 0);
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
