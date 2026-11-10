<?php

namespace Tests\Feature;

use App\Enums\AttachmentVisibility;
use App\Filament\Admin\Resources\CourseAttachments\Pages\ListCourseAttachments;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourseAttachmentCourseFilterTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->create(['name' => "attachments.{$action}", 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo(['attachments.viewAny', 'attachments.view']);

        $this->category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'attachment-filter-category',
            'is_active' => true,
        ]);
    }

    public function test_course_filter_matches_profile_and_section_attachments_of_that_course(): void
    {
        $course = $this->makeCourse('target');
        $otherCourse = $this->makeCourse('other');

        $direct = $this->makeAttachment(['course_id' => $course->id]);
        $viaSection = $this->makeAttachment([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $this->makeSectionInGroupOf($course)->id,
        ]);
        $otherDirect = $this->makeAttachment(['course_id' => $otherCourse->id]);
        $otherViaSection = $this->makeAttachment([
            'course_id' => $otherCourse->id,
            'course_curriculum_section_id' => $this->makeSectionInGroupOf($otherCourse)->id,
        ]);

        Livewire::test(ListCourseAttachments::class)
            ->assertCanSeeTableRecords([$direct, $viaSection, $otherDirect, $otherViaSection])
            ->filterTable('course_id', $course->id)
            ->assertCanSeeTableRecords([$direct, $viaSection])
            ->assertCanNotSeeTableRecords([$otherDirect, $otherViaSection]);
    }

    public function test_course_filter_combines_with_visibility_filter(): void
    {
        $course = $this->makeCourse('combined');

        $public = $this->makeAttachment(['course_id' => $course->id, 'visibility' => AttachmentVisibility::Public]);
        $profile = $this->makeAttachment(['course_id' => $course->id, 'visibility' => AttachmentVisibility::Profile]);

        Livewire::test(ListCourseAttachments::class)
            ->filterTable('course_id', $course->id)
            ->filterTable('visibility', AttachmentVisibility::Profile->value)
            ->assertCanSeeTableRecords([$profile])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_course_filter_shows_nothing_for_a_course_without_attachments(): void
    {
        $emptyCourse = $this->makeCourse('empty');
        $attachment = $this->makeAttachment(['course_id' => $this->makeCourse('busy')->id]);

        Livewire::test(ListCourseAttachments::class)
            ->filterTable('course_id', $emptyCourse->id)
            ->assertCanNotSeeTableRecords([$attachment])
            ->assertCountTableRecords(0);
    }

    private function makeCourse(string $slug): Course
    {
        return Course::query()->create([
            'category_id' => $this->category->id,
            'title' => ['en' => 'Course '.$slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'attachment-filter-'.$slug,
            'status' => 'published',
        ]);
    }

    private function makeSectionInGroupOf(Course $course): CourseCurriculumSection
    {
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group for '.$course->slug],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        return CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Section'],
            'sort_order' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeAttachment(array $attributes): CourseAttachment
    {
        return CourseAttachment::query()->create(array_merge([
            'title' => ['en' => 'Attachment'],
            'file_path' => ['attachments/file.pdf'],
            'visibility' => AttachmentVisibility::Public,
        ], $attributes));
    }
}
