<?php

namespace Tests\Feature\Nelc;

use App\Enums\NelcSyncStatus;
use App\Filament\Admin\Resources\NelcSyncLogs\Pages\ListNelcSyncLogs;
use App\Filament\Admin\Resources\NelcSyncLogs\Pages\ViewNelcSyncLog;
use App\Models\Category;
use App\Models\Course;
use App\Models\NelcSyncLog;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NelcSyncLogResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_permission_cannot_view_the_list(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        Livewire::test(ListNelcSyncLogs::class)->assertForbidden();
    }

    public function test_admin_with_permission_can_view_sync_logs(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->create(['name' => 'nelc_sync_logs.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);

        $log = NelcSyncLog::query()->create([
            'event_type' => 'Rated',
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payload' => ['verb' => ['id' => 'http://id.tincanapi.com/verb/rated']],
            'status' => NelcSyncStatus::Sent,
            'attempts' => 1,
            'synced_at' => now(),
        ]);

        Livewire::test(ListNelcSyncLogs::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$log]);

        Livewire::test(ViewNelcSyncLog::class, ['record' => $log->getKey()])
            ->assertOk()
            ->assertSee('Rated')
            ->assertSee($user->name);
    }
}
