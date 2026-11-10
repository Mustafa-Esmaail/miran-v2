<?php

namespace Tests\Feature;

use App\Enums\ChatRoomType;
use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTopbarChatQuickAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_topbar_shows_chat_quick_access_badge_when_unread_exists(): void
    {
        $admin = User::factory()->create();

        $sender = User::factory()->create();

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

        $message = $room->messages()->create([
            'sender_user_id' => $sender->id,
            'body' => 'Hello admin',
        ]);

        $admin->notify(new NewChatMessageNotification($message));

        $this->actingAs($admin, 'admin');

        $html = (string) view('filament.components.admin-topbar')->render();

        $this->assertStringContainsString('data-admin-chat-link', $html);
        $this->assertStringContainsString('data-admin-chat-unread-badge', $html);
        $this->assertMatchesRegularExpression('/data-admin-chat-unread-badge[\s\S]*>\s*1\s*</', $html);
    }
}
