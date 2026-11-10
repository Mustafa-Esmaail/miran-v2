<?php

namespace Tests\Feature;

use App\Livewire\Home\LeadForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_address_and_user_agent_are_recorded_on_submission(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'Tester')
            ->set('phone', '0555555501')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $serviceRequest = ServiceRequest::query()->first();

        $this->assertSame(request()->ip(), $serviceRequest->ip_address);
        $this->assertNotNull($serviceRequest->user_agent);
    }

    public function test_honeypot_field_is_accepted_silently_but_flagged_as_spam(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'Bot')
            ->set('phone', '0555555502')
            ->set('courseId', $course->id)
            ->set('companyFax', 'i-am-a-bot')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $serviceRequest = ServiceRequest::query()->where('phone', '966555555502')->first();

        $this->assertNotNull($serviceRequest);
        $this->assertSame(ServiceRequestStatus::spamBucket()->slug, $serviceRequest->status);
    }

    public function test_second_submission_from_same_ip_within_12_hours_is_blocked(): void
    {
        $course = $this->createPublishedCourse();

        // The combined phone-or-ip 12h guard blocks any second submission from that IP
        // (even with a different phone) before the burst limiter would ever be reached.
        Livewire::test(LeadForm::class)
            ->set('name', 'Lead 1')
            ->set('phone', '0555556010')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', true);

        Livewire::test(LeadForm::class)
            ->set('name', 'One too many')
            ->set('phone', '0555569999')
            ->set('courseId', $course->id)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors('phone')
            ->assertSee(__('web.home.lead_duplicate_today'));

        $this->assertDatabaseCount('service_requests', 1);
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
