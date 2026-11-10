<?php

namespace Tests\Feature;

use App\Enums\ChatRoomType;
use App\Enums\RoleInRoom;
use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\ChatRoomParticipant;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Policies\ChatRoomPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatRoomVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_chat_rooms_in_resource_query(): void
    {
        $admin = User::factory()->create();
        $this->grantChatRoomPermissions($admin, 'admin');

        [$firstRoom, $secondRoom] = $this->createTwoRooms();

        $this->actingAs($admin, 'admin');

        $roomIds = ChatRoomResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($firstRoom->id, $roomIds);
        $this->assertContains($secondRoom->id, $roomIds);
    }

    public function test_instructor_only_sees_rooms_where_they_are_active_participant(): void
    {
        $instructor = User::factory()->create();
        $this->grantChatRoomPermissions($instructor, 'instructor');

        [$ownRoom, $otherRoom] = $this->createTwoRooms();

        ChatRoomParticipant::query()->create([
            'chat_room_id' => $ownRoom->id,
            'user_id' => $instructor->id,
            'role_in_room' => RoleInRoom::Instructor,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->actingAs($instructor, 'admin');

        $roomIds = ChatRoomResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($ownRoom->id, $roomIds);
        $this->assertNotContains($otherRoom->id, $roomIds);
    }

    public function test_policy_blocks_instructor_from_viewing_unrelated_room(): void
    {
        $instructor = User::factory()->create();
        $this->grantChatRoomPermissions($instructor, 'instructor');

        [$ownRoom, $otherRoom] = $this->createTwoRooms();

        ChatRoomParticipant::query()->create([
            'chat_room_id' => $ownRoom->id,
            'user_id' => $instructor->id,
            'role_in_room' => RoleInRoom::Instructor,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->actingAs($instructor, 'admin');

        $policy = new ChatRoomPolicy;

        $this->assertTrue($policy->view($instructor, $ownRoom));
        $this->assertFalse($policy->view($instructor, $otherRoom));
    }

    /**
     * @return array{0: ChatRoom, 1: ChatRoom}
     */
    private function createTwoRooms(): array
    {
        $creator = User::factory()->create();

        $firstGroup = $this->createCourseGroup('group-one', 'Group One');
        $secondGroup = $this->createCourseGroup('group-two', 'Group Two');

        $firstRoom = ChatRoom::query()->create([
            'type' => ChatRoomType::CourseGroup,
            'course_id' => $firstGroup->course_id,
            'course_group_id' => $firstGroup->id,
            'created_by_user_id' => $creator->id,
            'title' => 'Group One Room',
            'is_active' => true,
        ]);

        $secondRoom = ChatRoom::query()->create([
            'type' => ChatRoomType::CourseGroup,
            'course_id' => $secondGroup->course_id,
            'course_group_id' => $secondGroup->id,
            'created_by_user_id' => $creator->id,
            'title' => 'Group Two Room',
            'is_active' => true,
        ]);

        return [$firstRoom, $secondRoom];
    }

    private function createCourseGroup(string $slug, string $title): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category '.uniqid()],
            'slug' => 'category-'.uniqid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course '.uniqid()],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title],
            'status' => 'open',
        ]);
    }

    private function grantChatRoomPermissions(User $user, string $roleName): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'admin',
        ]);

        $viewAnyPermission = Permission::query()->firstOrCreate([
            'name' => 'chat_rooms.viewAny',
            'guard_name' => 'admin',
        ]);

        $viewPermission = Permission::query()->firstOrCreate([
            'name' => 'chat_rooms.view',
            'guard_name' => 'admin',
        ]);

        $role->givePermissionTo([$viewAnyPermission, $viewPermission]);
        $user->assignRole($role);
    }
}
