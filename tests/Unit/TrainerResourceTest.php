<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Trainers\TrainerResource;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_instructors(): void
    {
        $instructor = User::factory()->create();
        $otherUser = User::factory()->create();
        $instructorRole = Role::findOrCreate('instructor', 'admin');

        $instructor->assignRole($instructorRole);

        $course = $this->createCourse();
        $instructor->teachingCourses()->attach($course->id);
        $otherUser->teachingCourses()->attach($course->id);

        $ids = TrainerResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($instructor->id));
        $this->assertFalse($ids->contains($otherUser->id));
    }

    public function test_it_requires_trainer_permission_to_view_any(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $this->assertFalse(TrainerResource::canViewAny());

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        Permission::query()->create([
            'name' => 'trainers.viewAny',
            'guard_name' => 'admin',
        ]);

        $admin->assignRole($role);
        $admin->givePermissionTo('trainers.viewAny');

        $this->assertTrue(TrainerResource::canViewAny());
    }

    public function test_it_allows_users_permission_to_view_any(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        Permission::query()->create([
            'name' => 'users.viewAny',
            'guard_name' => 'admin',
        ]);

        $admin->assignRole($role);
        $admin->givePermissionTo('users.viewAny');

        $this->assertTrue(TrainerResource::canViewAny());
    }

    private function createCourse(): Course
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
            'status' => 'draft',
        ]);
    }
}
