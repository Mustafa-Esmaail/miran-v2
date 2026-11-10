<?php

namespace Tests\Unit;

use App\Enums\ChatRoomType;
use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Filament\Widgets\AdminChatQuickAccessWidget;
use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminChatQuickAccessWidgetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_unread_chat_count_for_admin_notifications(): void
    {
        $admin = User::factory()->create();
        $sender = User::factory()->create();
        $message = $this->createChatMessage($sender);

        $admin->notify(new NewChatMessageNotification($message));
        $admin->notify(new NewChatMessageNotification($message));

        $this->actingAs($admin, 'admin');

        $widget = new AdminChatQuickAccessWidget;

        $this->assertSame(2, $widget->getUnreadChatCount());
    }

    #[Test]
    public function it_returns_chat_rooms_url_only_for_authorized_admin(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        $permission = Permission::query()->create([
            'name' => 'chat_rooms.viewAny',
            'guard_name' => 'admin',
        ]);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin');

        $widget = new AdminChatQuickAccessWidget;

        $this->assertSame(ChatRoomResource::getUrl('index'), $widget->getChatRoomsUrl());
    }

    private function createChatMessage(User $sender): \App\Models\ChatMessage
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        $room = ChatRoom::query()->create([
            'type' => ChatRoomType::Direct,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'created_by_user_id' => $sender->id,
            'title' => null,
            'is_active' => true,
        ]);

        return $room->messages()->create([
            'sender_user_id' => $sender->id,
            'body' => 'Hello admin',
        ]);
    }
}
