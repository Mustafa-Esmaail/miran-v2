<?php

namespace Tests\Feature;

use App\Enums\ChatRoomType;
use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_existing_trainer_role_data_into_instructor(): void
    {
        $user = User::factory()->create();
        $trainerRole = Role::query()->firstOrCreate(['name' => 'trainer', 'guard_name' => 'admin']);
        $instructorRole = Role::query()->firstOrCreate(['name' => 'instructor', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'courses.viewAny', 'guard_name' => 'admin']);

        $trainerRole->givePermissionTo($permission);
        $user->assignRole($trainerRole);

        $room = $this->createChatRoom($user);
        DB::table('chat_room_participants')->insert([
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'role_in_room' => 'trainer',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = include database_path('migrations/2026_04_30_150400_rename_trainer_role_to_instructor.php');
        $migration->up();

        $this->assertDatabaseMissing('roles', [
            'name' => 'trainer',
            'guard_name' => 'admin',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $instructorRole->id,
            'name' => 'instructor',
            'guard_name' => 'admin',
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $instructorRole->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $permission->id,
            'role_id' => $instructorRole->id,
        ]);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'role_in_room' => 'instructor',
        ]);
    }

    private function createChatRoom(User $creator): ChatRoom
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => fake()->unique()->slug(),
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        return ChatRoom::query()->create([
            'type' => ChatRoomType::CourseGroup,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'created_by_user_id' => $creator->id,
            'title' => 'Group Room',
            'is_active' => true,
        ]);
    }
}
