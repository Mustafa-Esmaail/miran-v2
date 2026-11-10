<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ShareLinks\Pages\CreateShareLink;
use App\Models\Category;
use App\Models\Employee;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShareLinkCategoryTargetTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach (['viewAny', 'view', 'create'] as $action) {
            Permission::query()->create(['name' => "share_links.{$action}", 'guard_name' => 'admin']);
        }
        Permission::query()->create(['name' => 'employees.viewAny', 'guard_name' => 'admin']);
        $admin->givePermissionTo(['share_links.viewAny', 'share_links.view', 'share_links.create', 'employees.viewAny']);

        $this->employee = Employee::factory()->create([
            'user_id' => $admin->id,
            'employee_code' => 'DG-90',
            'is_active' => true,
        ]);

        $this->category = Category::query()->create([
            'name' => ['en' => 'Leadership', 'ar' => 'القيادة'],
            'slug' => 'leadership',
            'is_active' => true,
        ]);
    }

    public function test_category_link_points_to_courses_filtered_by_category_with_source(): void
    {
        $link = ShareLink::query()->create([
            'employee_id' => $this->employee->id,
            'target_type' => 'category',
            'target_id' => $this->category->id,
        ]);

        $this->assertSame(
            route('courses.index').'?categoryId='.$this->category->id.'&source=DG-90',
            $link->full_url
        );

        parse_str((string) parse_url($link->full_url, PHP_URL_QUERY), $query);
        $this->assertSame(['categoryId' => (string) $this->category->id, 'source' => 'DG-90'], $query);
    }

    public function test_creating_a_category_share_link_from_the_form_stores_label_and_url(): void
    {
        Livewire::test(CreateShareLink::class)
            ->fillForm([
                'employee_id' => $this->employee->id,
                'target_type' => 'category',
                'target_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('share_links', [
            'employee_id' => $this->employee->id,
            'target_type' => 'category',
            'target_id' => $this->category->id,
            'target_label' => 'Leadership',
            'full_url' => route('courses.index').'?categoryId='.$this->category->id.'&source=DG-90',
        ]);
    }

    public function test_category_target_requires_selecting_a_category(): void
    {
        Livewire::test(CreateShareLink::class)
            ->fillForm([
                'employee_id' => $this->employee->id,
                'target_type' => 'category',
                'target_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['target_id' => 'required']);

        $this->assertDatabaseCount('share_links', 0);
    }

    public function test_inactive_categories_are_not_offered(): void
    {
        $inactive = Category::query()->create([
            'name' => ['en' => 'Archived'],
            'slug' => 'archived',
            'is_active' => false,
        ]);

        Livewire::test(CreateShareLink::class)
            ->fillForm([
                'employee_id' => $this->employee->id,
                'target_type' => 'category',
                'target_id' => $inactive->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['target_id']);
    }

    public function test_visiting_the_category_link_credits_the_employee(): void
    {
        $link = ShareLink::query()->create([
            'employee_id' => $this->employee->id,
            'target_type' => 'category',
            'target_id' => $this->category->id,
        ]);

        $this->get($link->full_url)
            ->assertOk()
            ->assertSessionHas('referral_employee_id', $this->employee->id)
            ->assertSessionHas('marketing_source', 'DG-90');
    }
}
