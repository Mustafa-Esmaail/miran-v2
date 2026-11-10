<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EnrollmentBulkStatusActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(bool $canUpdate = true): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $permissions = ['enrollments.viewAny', 'enrollments.view', 'courses.viewAny', 'course_groups.viewAny'];

        if ($canUpdate) {
            $permissions[] = 'enrollments.update';
        }

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function makeGroup(string $slug): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => $slug.'-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);
    }

    private function makeEnrollment(CourseGroup $group, EnrollmentStatus $status, bool $activated = false): Enrollment
    {
        return Enrollment::query()->create([
            'user_id' => User::factory()->create()->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => $status,
            'activated_at' => $activated ? now()->subDay() : null,
        ]);
    }

    public function test_bulk_activate_sets_status_and_stamps_activated_at(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup('bulk-activate');
        $first = $this->makeEnrollment($group, EnrollmentStatus::Pending);
        $second = $this->makeEnrollment($group, EnrollmentStatus::Cancelled);

        Livewire::test(ListEnrollments::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('change_status')->table()->bulk(), data: [
                'status' => EnrollmentStatus::Active->value,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        foreach ([$first, $second] as $enrollment) {
            $enrollment->refresh();
            $this->assertSame(EnrollmentStatus::Active, $enrollment->status);
            $this->assertNotNull($enrollment->activated_at);
        }
    }

    public function test_bulk_activate_keeps_existing_activated_at(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup('bulk-keep-date');
        $enrollment = $this->makeEnrollment($group, EnrollmentStatus::PendingCertificate, activated: true);
        $originalActivatedAt = $enrollment->activated_at->toDateTimeString();

        Livewire::test(ListEnrollments::class)
            ->selectTableRecords([$enrollment->id])
            ->callAction(TestAction::make('change_status')->table()->bulk(), data: [
                'status' => EnrollmentStatus::Active->value,
            ]);

        $this->assertSame($originalActivatedAt, $enrollment->fresh()->activated_at->toDateTimeString());
    }

    public function test_bulk_cancel_clears_activated_at(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup('bulk-cancel');
        $enrollment = $this->makeEnrollment($group, EnrollmentStatus::Active, activated: true);
        $untouched = $this->makeEnrollment($group, EnrollmentStatus::Active, activated: true);

        Livewire::test(ListEnrollments::class)
            ->selectTableRecords([$enrollment->id])
            ->callAction(TestAction::make('change_status')->table()->bulk(), data: [
                'status' => EnrollmentStatus::Cancelled->value,
            ]);

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Cancelled, $enrollment->status);
        $this->assertNull($enrollment->activated_at);
        $this->assertSame(EnrollmentStatus::Active, $untouched->fresh()->status);
    }

    public function test_status_is_required(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup('bulk-required');
        $enrollment = $this->makeEnrollment($group, EnrollmentStatus::Pending);

        Livewire::test(ListEnrollments::class)
            ->selectTableRecords([$enrollment->id])
            ->callAction(TestAction::make('change_status')->table()->bulk(), data: [
                'status' => null,
            ])
            ->assertHasFormErrors(['status' => 'required']);

        $this->assertSame(EnrollmentStatus::Pending, $enrollment->fresh()->status);
    }

    public function test_bulk_change_status_is_hidden_without_update_permission(): void
    {
        $this->actingAsAdmin(canUpdate: false);

        Livewire::test(ListEnrollments::class)
            ->assertActionHidden(TestAction::make('change_status')->table()->bulk());
    }
}
