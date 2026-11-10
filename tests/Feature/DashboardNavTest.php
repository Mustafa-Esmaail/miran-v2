<?php

namespace Tests\Feature;

use App\Enums\ChatRoomType;
use App\Enums\RoleInRoom;
use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\View\Components\DashboardNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_nav_renders_menu_items_for_user_dashboard_location(): void
    {
        $menu = Menu::query()->create([
            'name' => 'User Dashboard',
            'location' => 'user_dashboard',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => ['en' => 'Help Center'],
            'type' => 'custom',
            'url' => '/help-center',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $component = new DashboardNav;
        $html = view('components.dashboard-nav', ['links' => $component->links])->render();

        $this->assertStringContainsString('Help Center', $html);
    }

    public function test_dashboard_nav_renders_children_as_dropdown_items(): void
    {
        $menu = Menu::query()->create([
            'name' => 'User Dashboard',
            'location' => 'user_dashboard',
            'is_active' => true,
        ]);

        $parent = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => ['en' => 'Resources'],
            'type' => 'custom',
            'url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => ['en' => 'FAQ'],
            'type' => 'custom',
            'url' => '/faq',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $component = new DashboardNav;
        $html = view('components.dashboard-nav', ['links' => $component->links])->render();

        $this->assertStringContainsString('Resources', $html);
        $this->assertStringContainsString('FAQ', $html);
        $this->assertStringContainsString('<summary', $html);
        $this->assertStringContainsString('href="/faq"', $html);
    }

    public function test_dashboard_nav_includes_messages_link(): void
    {
        $component = new DashboardNav('messages');
        $html = view('components.dashboard-nav', ['links' => $component->links])->render();

        $this->assertStringContainsString(route('dashboard.messages'), $html);
        $this->assertStringContainsString(__('web.chat.title'), $html);
    }

    public function test_dashboard_nav_shows_unread_badge_on_messages_link_for_student(): void
    {
        $student = User::factory()->create();
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
            'type' => ChatRoomType::CourseGroup,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => 'Group Chat',
            'is_active' => true,
            'created_by_user_id' => $sender->id,
        ]);

        $message = $room->messages()->create([
            'sender_user_id' => $sender->id,
            'body' => 'Unread message',
        ]);

        $room->update([
            'last_message_at' => $message->created_at,
            'last_message_by_user_id' => $sender->id,
        ]);

        $room->participants()->create([
            'user_id' => $student->id,
            'role_in_room' => RoleInRoom::Student->value,
            'joined_at' => now(),
            'last_read_message_id' => null,
        ]);

        $this->actingAs($student, 'web');

        $component = new DashboardNav('messages');
        $html = view('components.dashboard-nav', ['links' => $component->links])->render();

        $this->assertStringContainsString(route('dashboard.messages'), $html);
        $this->assertStringContainsString('data-dashboard-nav-badge="messages"', $html);
        $this->assertMatchesRegularExpression('/data-dashboard-nav-badge="messages"[^>]*>\s*1\s*</', $html);
    }
}
