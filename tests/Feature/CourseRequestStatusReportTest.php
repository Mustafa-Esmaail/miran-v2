<?php

namespace Tests\Feature;

use App\Filament\Pages\CourseRequestStatusReport;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourseRequestStatusReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithReportAccess(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');
        Permission::query()->firstOrCreate(['name' => 'course_request_status_report.viewAny', 'guard_name' => 'admin']);
        $admin->givePermissionTo('course_request_status_report.viewAny');

        return $admin;
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Report Course', 'ar' => 'Report Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'report-course',
            'status' => 'published',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCourseRequest(Course $course, array $attributes = []): ServiceRequest
    {
        return ServiceRequest::factory()->create(array_merge([
            'course_id' => $course->id,
            'status' => 'new',
        ], $attributes));
    }

    public function test_it_denies_access_without_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'admin');

        $this->assertFalse(CourseRequestStatusReport::canAccess());
    }

    public function test_it_opens_on_all_time_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(CourseRequestStatusReport::class)
            ->assertOk()
            ->assertSet('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->set('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString());
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(CourseRequestStatusReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_last_month_preset_excludes_this_months_requests(): void
    {
        $this->actingAsAdminWithReportAccess();

        $course = $this->createCourse();
        $this->createCourseRequest($course);
        $this->createCourseRequest($course)
            ->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2)])
            ->save();

        $allRows = $this->callBuildRows(Livewire::test(CourseRequestStatusReport::class)->instance());
        $this->assertSame(2, $allRows->firstWhere('type', 'course')['total']);

        $page = Livewire::test(CourseRequestStatusReport::class)
            ->set('filters.date_range', 'last_month')
            ->instance();

        $this->assertSame(1, $this->callBuildRows($page)->firstWhere('type', 'course')['total']);
    }

    public function test_it_exports_csv(): void
    {
        $this->actingAsAdminWithReportAccess();

        $this->createCourseRequest($this->createCourse());

        Livewire::test(CourseRequestStatusReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithReportAccess();

        $this->createCourseRequest($this->createCourse());

        Livewire::test(CourseRequestStatusReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    private function callBuildRows(CourseRequestStatusReport $page): Collection
    {
        return (new ReflectionMethod(CourseRequestStatusReport::class, 'buildRows'))->invoke($page);
    }
}
