<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Employees\RelationManagers\ShareLinksRelationManager;
use App\Filament\Admin\Resources\ShareLinks\Pages\ListShareLinks;
use App\Models\Category;
use App\Models\Course;
use App\Models\Employee;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShareLinksTableTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Category $category;

    private Category $otherCategory;

    private Course $course;

    private Course $otherCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach (['share_links.viewAny', 'share_links.view', 'employees.viewAny', 'employees.view', 'employees.update'] as $name) {
            Permission::query()->create(['name' => $name, 'guard_name' => 'admin']);
            $admin->givePermissionTo($name);
        }

        $this->employee = Employee::factory()->create(['employee_code' => 'DG-90', 'is_active' => true]);

        $this->category = $this->makeCategory('leadership', 'Leadership');
        $this->otherCategory = $this->makeCategory('finance', 'Finance');
        $this->course = $this->makeCourse('course-a', 'Course A');
        $this->otherCourse = $this->makeCourse('course-b', 'Course B');
    }

    public function test_copy_column_copies_the_full_url(): void
    {
        $link = $this->makeLink('category', $this->category->id);

        Livewire::test(ListShareLinks::class)
            ->assertTableColumnExists('full_url_copy')
            ->assertTableColumnStateSet('full_url_copy', $link->full_url, $link);
    }

    public function test_columns_are_ordered_employee_target_link_then_type(): void
    {
        $names = collect(Livewire::test(ListShareLinks::class)->instance()->getTable()->getColumns())
            ->keys()
            ->filter(fn (string $name): bool => in_array($name, ['employee.name', 'target_label', 'full_url', 'full_url_copy', 'target_type'], true))
            ->values()
            ->all();

        $this->assertSame(['employee.name', 'target_label', 'full_url', 'full_url_copy', 'target_type'], $names);
    }

    public function test_employee_column_shows_code_and_name_and_searches_both(): void
    {
        $this->employee->update(['name' => 'Sara Ali']);
        $other = Employee::factory()->create(['employee_code' => 'ZZ-11', 'name' => 'Omar Hadi', 'is_active' => true]);
        $saraLink = $this->makeLink('homepage');
        $omarLink = ShareLink::query()->create(['employee_id' => $other->id, 'target_type' => 'homepage']);

        Livewire::test(ListShareLinks::class)
            ->assertTableColumnFormattedStateSet('employee.name', 'DG-90 - Sara Ali', $saraLink)
            ->searchTable('DG-90')
            ->assertCanSeeTableRecords([$saraLink])
            ->assertCanNotSeeTableRecords([$omarLink])
            ->searchTable('Omar')
            ->assertCanSeeTableRecords([$omarLink])
            ->assertCanNotSeeTableRecords([$saraLink]);
    }

    public function test_target_filter_by_type_only(): void
    {
        $homepage = $this->makeLink('homepage');
        $courseA = $this->makeLink('course', $this->course->id);
        $courseB = $this->makeLink('course', $this->otherCourse->id);
        $category = $this->makeLink('category', $this->category->id);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => 'course'])
            ->assertCanSeeTableRecords([$courseA, $courseB])
            ->assertCanNotSeeTableRecords([$homepage, $category]);
    }

    public function test_target_filter_by_specific_course(): void
    {
        $courseA = $this->makeLink('course', $this->course->id);
        $courseB = $this->makeLink('course', $this->otherCourse->id);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => 'course', 'target_id' => $this->course->id])
            ->assertCanSeeTableRecords([$courseA])
            ->assertCanNotSeeTableRecords([$courseB]);
    }

    public function test_target_filter_by_specific_category(): void
    {
        $leadership = $this->makeLink('category', $this->category->id);
        $finance = $this->makeLink('category', $this->otherCategory->id);
        $courseWithSameId = $this->makeLink('course', $this->category->id);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => 'category', 'target_id' => $this->category->id])
            ->assertCanSeeTableRecords([$leadership])
            ->assertCanNotSeeTableRecords([$finance, $courseWithSameId]);
    }

    public function test_target_filter_by_specific_bundle(): void
    {
        $bundleOne = $this->makeLink('bundle', 1);
        $bundleTwo = $this->makeLink('bundle', 2);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => 'bundle', 'target_id' => 1])
            ->assertCanSeeTableRecords([$bundleOne])
            ->assertCanNotSeeTableRecords([$bundleTwo]);
    }

    public function test_homepage_filter_ignores_a_leftover_target_id(): void
    {
        $homepage = $this->makeLink('homepage');
        $course = $this->makeLink('course', $this->course->id);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => 'homepage', 'target_id' => $this->course->id])
            ->assertCanSeeTableRecords([$homepage])
            ->assertCanNotSeeTableRecords([$course]);
    }

    public function test_empty_target_filter_shows_everything(): void
    {
        $homepage = $this->makeLink('homepage');
        $course = $this->makeLink('course', $this->course->id);

        Livewire::test(ListShareLinks::class)
            ->filterTable('target', ['target_type' => null, 'target_id' => null])
            ->assertCanSeeTableRecords([$homepage, $course]);
    }

    public function test_employee_share_links_tab_has_copy_column_and_target_filter(): void
    {
        $courseA = $this->makeLink('course', $this->course->id);
        $category = $this->makeLink('category', $this->category->id);

        Livewire::test(ShareLinksRelationManager::class, [
            'ownerRecord' => $this->employee,
            'pageClass' => EditEmployee::class,
        ])
            ->assertTableColumnStateSet('full_url_copy', $courseA->full_url, $courseA)
            ->filterTable('target', ['target_type' => 'course', 'target_id' => $this->course->id])
            ->assertCanSeeTableRecords([$courseA])
            ->assertCanNotSeeTableRecords([$category]);
    }

    private function makeLink(string $type, ?int $targetId = null): ShareLink
    {
        return ShareLink::query()->create([
            'employee_id' => $this->employee->id,
            'target_type' => $type,
            'target_id' => $targetId,
        ]);
    }

    private function makeCategory(string $slug, string $name): Category
    {
        return Category::query()->create([
            'name' => ['en' => $name],
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeCourse(string $slug, string $title): Course
    {
        return Course::query()->create([
            'category_id' => $this->category->id,
            'title' => ['en' => $title],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);
    }
}
