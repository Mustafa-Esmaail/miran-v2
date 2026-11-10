<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Livewire\Home\LeadForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\ServiceRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_creates_service_request_and_activity(): void
    {
        $course = $this->createPublishedCourse();

        Livewire::test(LeadForm::class)
            ->set('name', 'LMS Lead')
            ->set('phone', '0555555555')
            ->set('courseId', $course->id)
            ->set('message', 'Interested in a course')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('service_requests', 1);
        $this->assertDatabaseHas('service_requests', [
            'source' => ServiceRequestSource::Lead->value,
            'name' => 'LMS Lead',
            'phone' => '966555555555',
            'course_id' => $course->id,
            'status' => ServiceRequestStatus::default()->slug,
        ]);

        $request = ServiceRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertDatabaseHas('service_request_activities', [
            'service_request_id' => $request->id,
            'type' => 'created',
        ]);

        $this->assertSame(1, ServiceRequestActivity::query()->count());
    }

    public function test_lead_form_requires_course_selection_when_course_select_is_shown(): void
    {
        Livewire::test(LeadForm::class)
            ->set('name', 'LMS Lead')
            ->set('phone', '0555555555')
            ->set('courseId', null)
            ->set('message', 'Interested in a course')
            ->call('submit')
            ->assertHasErrors(['courseId' => 'required']);

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
