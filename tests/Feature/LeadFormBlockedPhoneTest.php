<?php

namespace Tests\Feature;

use App\Livewire\Home\LeadForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Services\ServiceRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormBlockedPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_marked_as_spam_is_blocked_from_submitting_again(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'status' => 'spam',
            'created_at' => now()->subMonths(3),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Blocked')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('phone')
            ->assertSee(__('web.home.lead_phone_blocked'));

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_unrelated_phone_is_not_blocked_by_another_phones_spam_status(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'status' => 'spam',
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Fine')
            ->set('phone', '0555555556')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_phone_is_unblocked_once_its_spam_flagged_request_status_changes(): void
    {
        $course = $this->createPublishedCourse();

        $request = ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'status' => 'spam',
            'created_at' => now()->subMonths(3),
        ]);

        $request->update(['status' => 'wrong_number']);

        Livewire::test(LeadForm::class)
            ->set('name', 'No longer blocked')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_no_active_spam_status_configured_does_not_block_anyone(): void
    {
        $course = $this->createPublishedCourse();

        ServiceRequestStatus::query()->where('slug', 'spam')->update(['is_active' => false]);

        ServiceRequest::factory()->create([
            'phone' => ServiceRequestService::normalizePhone('0555555555'),
            'status' => 'spam',
            'created_at' => now()->subMonths(3),
        ]);

        Livewire::test(LeadForm::class)
            ->set('name', 'Allowed')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 2);
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
