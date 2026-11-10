<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Assignments\Pages\CreateAssignment;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignmentInstructorScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_only_sees_assignments_for_their_own_group(): void
    {
        [$instructor, $course, $group] = $this->createInstructorWithCourse('Own Course', 'Own Group');
        [, $otherCourse, $otherGroup] = $this->createInstructorWithCourse('Other Course', 'Other Group');

        $this->createAssignment($course, $group, 'Own Assignment');
        $this->createAssignment($otherCourse, $otherGroup, 'Other Assignment');

        $response = $this->actingAs($instructor, 'admin')->get('/admin/assignments');

        $response->assertOk();
        $response->assertSee('Own Assignment');
        $response->assertDontSee('Other Assignment');
    }

    public function test_instructor_create_form_only_offers_their_own_course_and_group(): void
    {
        [$instructor, $course, $group] = $this->createInstructorWithCourse('Own Course', 'Own Group');
        [, $otherCourse, $otherGroup] = $this->createInstructorWithCourse('Other Course', 'Other Group');

        $this->actingAs($instructor, 'admin');

        $component = Livewire::test(CreateAssignment::class);

        $courseField = collect($component->instance()->getSchema('form')->getFlatFields(withHidden: true))
            ->first(fn ($field) => $field->getName() === 'course_id');

        $courseOptions = $courseField->getOptions();

        $this->assertArrayHasKey($course->id, $courseOptions);
        $this->assertArrayNotHasKey($otherCourse->id, $courseOptions);

        $component->fillForm(['course_id' => $course->id]);

        $groupField = collect($component->instance()->getSchema('form')->getFlatFields(withHidden: true))
            ->first(fn ($field) => $field->getName() === 'course_group_id');

        $groupOptions = $groupField->getOptions();

        $this->assertArrayHasKey($group->id, $groupOptions);
        $this->assertArrayNotHasKey($otherGroup->id, $groupOptions);
    }

    /**
     * @return array{0: User, 1: Course, 2: CourseGroup}
     */
    private function createInstructorWithCourse(string $courseTitle, string $groupTitle): array
    {
        $instructorRole = Role::findOrCreate('instructor', 'admin');
        $instructorRole->givePermissionTo(
            collect(['assignments.viewAny', 'assignments.view', 'assignments.create', 'assignments.update'])
                ->map(fn (string $name) => Permission::findOrCreate($name, 'admin'))
        );

        $instructor = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $instructor->assignRole($instructorRole);

        $category = Category::query()->create([
            'name' => ['en' => $courseTitle.' Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $courseTitle, 'ar' => $courseTitle],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $groupTitle, 'ar' => $groupTitle],
            'status' => 'open',
        ]);

        $group->instructors()->sync([$instructor->id]);

        return [$instructor, $course, $group];
    }

    private function createAssignment(Course $course, CourseGroup $group, string $title): Assignment
    {
        $section = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title.' Section'],
            'description' => ['en' => 'Description'],
            'sort_order' => 0,
        ]);

        return Assignment::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $section->id,
            'title' => ['en' => $title, 'ar' => $title],
            'max_grade' => 10,
        ]);
    }
}
