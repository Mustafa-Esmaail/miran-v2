<?php

namespace Tests\Feature;

use App\Filament\Pages\LmsEnrollmentReport;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LmsEnrollmentReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function createEnrollment(CourseGroup $group, Carbon $day): void
    {
        $user = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-LER-'.uniqid(),
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => $group->group_price_amount,
            'discount_amount' => 0,
            'total_amount' => $group->group_price_amount,
            'source' => 'store',
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group',
            'price_amount' => $group->group_price_amount,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        $enrollment->forceFill(['created_at' => $day])->saveQuietly();
    }

    public function test_it_builds_a_group_by_day_pivot_of_enrollment_counts(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'ler-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'PMP'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'ler-pmp',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group B'],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);

        $day1 = Carbon::parse('2026-08-03');
        $day2 = Carbon::parse('2026-08-04');

        $this->createEnrollment($groupA, $day1);
        $this->createEnrollment($groupA, $day1);
        $this->createEnrollment($groupB, $day1);
        $this->createEnrollment($groupB, $day2);
        $this->createEnrollment($groupB, $day2);
        $this->createEnrollment($groupB, $day2);

        $rows = Livewire::test(LmsEnrollmentReport::class)
            ->set('filters.date_from', $day1->toDateString())
            ->set('filters.date_to', $day2->toDateString())
            ->set('filters.course_id', $course->id)
            ->instance()
            ->getTableRecords();

        $rowA = $rows->firstWhere('group', 'PMP — Group A');
        $rowB = $rows->firstWhere('group', 'PMP — Group B');

        $this->assertSame(2, $rowA['day_'.$day1->toDateString()]);
        $this->assertSame(0, $rowA['day_'.$day2->toDateString()]);

        $this->assertSame(1, $rowB['day_'.$day1->toDateString()]);
        $this->assertSame(3, $rowB['day_'.$day2->toDateString()]);
    }

    public function test_course_group_filter_narrows_rows_to_the_selected_group(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'ler-category-2',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'CAPM'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'ler-capm',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group B'],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);

        $day = Carbon::parse('2026-08-03');
        $this->createEnrollment($groupA, $day);
        $this->createEnrollment($groupB, $day);

        $rows = Livewire::test(LmsEnrollmentReport::class)
            ->set('filters.date_from', $day->toDateString())
            ->set('filters.date_to', $day->toDateString())
            ->set('filters.course_id', $course->id)
            ->set('filters.course_group_id', $groupA->id)
            ->instance()
            ->getTableRecords();

        $this->assertCount(1, $rows);
        $this->assertSame('CAPM — Group A', $rows->first()['group']);
        $this->assertSame(1, $rows->first()['day_'.$day->toDateString()]);
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
            'title' => ['en' => 'Course '.$slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group '.$slug],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);
    }

    public function test_it_opens_on_this_month_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        Livewire::test(LmsEnrollmentReport::class)
            ->assertSet('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString())
            ->set('filters.date_range', 'last_7_days')
            ->assertSet('filters.date_from', now()->subDays(6)->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null);
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        Livewire::test(LmsEnrollmentReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_all_preset_spans_from_the_earliest_enrollment_to_today(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        $group = $this->makeGroup('ler-all');
        $earliest = now()->subDays(3)->startOfDay();
        $this->createEnrollment($group, $earliest);
        $this->createEnrollment($group, now());

        $rows = Livewire::test(LmsEnrollmentReport::class)
            ->set('filters.date_range', 'all')
            ->instance()
            ->getTableRecords();

        $row = $rows->firstWhere('group', 'Course ler-all — Group ler-all');

        $this->assertSame(1, $row['day_'.$earliest->toDateString()]);
        $this->assertSame(1, $row['day_'.now()->toDateString()]);
        $this->assertArrayNotHasKey('day_'.$earliest->copy()->subDay()->toDateString(), $row);
        $this->assertArrayNotHasKey('day_'.now()->addDay()->toDateString(), $row);
    }

    public function test_it_exports_csv_and_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['lms_enrollment_report.viewAny']);

        $group = $this->makeGroup('ler-export');
        $this->createEnrollment($group, now());

        Livewire::test(LmsEnrollmentReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(LmsEnrollmentReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }
}
