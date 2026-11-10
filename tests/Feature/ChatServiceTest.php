<?php

namespace Tests\Feature;

use App\Enums\ChatRoomType;
use App\Enums\EnrollmentStatus;
use App\Enums\RoleInRoom;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomParticipant;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\CourseInstructor;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use App\Notifications\NewCourseGroupContentNotification;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    private User $trainer;

    private User $student;

    private User $admin;

    private Course $course;

    private CourseGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->service = app(ChatService::class);

        $category = Category::create([
            'name' => ['ar' => 'Test Category'],
            'slug' => 'test-cat',
            'is_active' => true,
        ]);

        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->trainer = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Test Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'test-course',
            'status' => 'published',
        ]);

        $this->course->trainers()->attach($this->trainer->id);

        $this->group = CourseGroup::create([
            'course_id' => $this->course->id,
            'title' => ['ar' => 'Group 1'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::withoutEvents(function (): void {
            Enrollment::query()->create([
                'user_id' => $this->student->id,
                'course_id' => $this->course->id,
                'course_group_id' => $this->group->id,
                'status' => EnrollmentStatus::Active,
                'activated_at' => now(),
            ]);
        });
    }

    public function test_find_or_create_group_room_creates_room_with_participants(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->assertDatabaseHas('chat_rooms', [
            'course_group_id' => $this->group->id,
            'type' => ChatRoomType::CourseGroup->value,
        ]);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $this->student->id,
            'role_in_room' => RoleInRoom::Student->value,
        ]);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $this->trainer->id,
            'role_in_room' => RoleInRoom::Instructor->value,
        ]);
    }

    public function test_group_specific_instructor_is_seeded_only_into_their_own_group_room(): void
    {
        $otherGroup = CourseGroup::create([
            'course_id' => $this->course->id,
            'title' => ['ar' => 'Group 2'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $groupOnlyTrainer = User::factory()->create();
        CourseInstructor::query()->create([
            'course_id' => $this->course->id,
            'course_group_id' => $this->group->id,
            'user_id' => $groupOnlyTrainer->id,
        ]);

        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $otherRoom = $this->service->findOrCreateGroupRoom($otherGroup, $this->trainer);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $groupOnlyTrainer->id,
            'role_in_room' => RoleInRoom::Instructor->value,
        ]);

        $this->assertDatabaseMissing('chat_room_participants', [
            'chat_room_id' => $otherRoom->id,
            'user_id' => $groupOnlyTrainer->id,
        ]);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $otherRoom->id,
            'user_id' => $this->trainer->id,
            'role_in_room' => RoleInRoom::Instructor->value,
        ]);
    }

    public function test_find_or_create_group_room_returns_existing_room(): void
    {
        $first = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $second = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ChatRoom::query()->where('type', ChatRoomType::CourseGroup)->count());
    }

    public function test_find_or_create_direct_room_succeeds_for_same_group_members(): void
    {
        $room = $this->service->findOrCreateDirectRoom($this->student, $this->trainer, $this->group);

        $this->assertDatabaseHas('chat_rooms', [
            'course_group_id' => $this->group->id,
            'type' => ChatRoomType::Direct->value,
        ]);

        $this->assertSame(2, ChatRoomParticipant::query()->where('chat_room_id', $room->id)->count());
    }

    public function test_find_or_create_direct_room_returns_existing_room(): void
    {
        $first = $this->service->findOrCreateDirectRoom($this->student, $this->trainer, $this->group);
        $second = $this->service->findOrCreateDirectRoom($this->trainer, $this->student, $this->group);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ChatRoom::query()->where('type', ChatRoomType::Direct)->count());
    }

    public function test_direct_room_denied_for_outsider(): void
    {
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        $this->service->findOrCreateDirectRoom($this->student, $outsider, $this->group);
    }

    public function test_send_message_succeeds_for_participant(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $message = $this->service->sendMessage($this->student, $room, 'Hello everyone');

        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room->id,
            'sender_user_id' => $this->student->id,
            'body' => 'Hello everyone',
        ]);

        $room->refresh();
        $this->assertNotNull($room->last_message_at);
        $this->assertSame($this->student->id, $room->last_message_by_user_id);
    }

    public function test_send_message_denied_for_non_participant(): void
    {
        $outsider = User::factory()->create();
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->expectException(AuthorizationException::class);

        $this->service->sendMessage($outsider, $room, 'Should fail');
    }

    public function test_send_message_denied_on_read_only_room(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $room->update(['read_only_at' => now()]);

        $this->expectException(AuthorizationException::class);

        $this->service->sendMessage($this->student, $room, 'Should fail');
    }

    public function test_mark_room_read_updates_last_read_message_id(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $message = $this->service->sendMessage($this->trainer, $room, 'Hi students');

        $this->service->markRoomRead($this->student, $room);

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $this->student->id,
            'last_read_message_id' => $message->id,
        ]);
    }

    public function test_trainer_can_hide_message(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $message = $this->service->sendMessage($this->student, $room, 'Bad message');

        $hidden = $this->service->hideMessage($this->trainer, $message, 'Inappropriate');

        $this->assertNotNull($hidden->hidden_at);
        $this->assertSame($this->trainer->id, $hidden->hidden_by_user_id);
        $this->assertDatabaseHas('chat_message_actions', [
            'chat_message_id' => $message->id,
            'actor_user_id' => $this->trainer->id,
            'action' => 'hidden',
        ]);
    }

    public function test_student_cannot_hide_message(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $message = $this->service->sendMessage($this->trainer, $room, 'Hello');

        $this->expectException(AuthorizationException::class);

        $this->service->hideMessage($this->student, $message, 'Reason');
    }

    public function test_trainer_can_unhide_message(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $message = $this->service->sendMessage($this->student, $room, 'Bad message');
        $this->service->hideMessage($this->trainer, $message, 'Inappropriate');

        $unhidden = $this->service->unhideMessage($this->trainer, $message->refresh());

        $this->assertNull($unhidden->hidden_at);
    }

    public function test_enrollment_observer_adds_student_to_group_room(): void
    {
        $newStudent = User::factory()->create();

        Enrollment::query()->create([
            'user_id' => $newStudent->id,
            'course_id' => $this->course->id,
            'course_group_id' => $this->group->id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ]);

        $room = ChatRoom::query()
            ->where('course_group_id', $this->group->id)
            ->where('type', ChatRoomType::CourseGroup)
            ->firstOrFail();

        $this->assertDatabaseHas('chat_room_participants', [
            'chat_room_id' => $room->id,
            'user_id' => $newStudent->id,
            'role_in_room' => RoleInRoom::Student->value,
        ]);
    }

    public function test_enrollment_observer_creates_group_and_direct_rooms_with_welcome_messages(): void
    {
        $newStudent = User::factory()->create();

        Enrollment::query()->create([
            'user_id' => $newStudent->id,
            'course_id' => $this->course->id,
            'course_group_id' => $this->group->id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ]);

        $groupRoom = ChatRoom::query()
            ->where('course_group_id', $this->group->id)
            ->where('type', ChatRoomType::CourseGroup)
            ->first();

        $directRoom = ChatRoom::query()
            ->where('course_group_id', $this->group->id)
            ->where('type', ChatRoomType::Direct)
            ->first();

        $this->assertNotNull($groupRoom);
        $this->assertNotNull($directRoom);
        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $groupRoom->id,
            'sender_user_id' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $directRoom->id,
            'sender_user_id' => $this->admin->id,
        ]);
    }

    public function test_find_or_create_direct_room_seeds_a_single_welcome_message(): void
    {
        $room = $this->service->findOrCreateDirectRoom($this->student, $this->trainer, $this->group);

        $this->assertSame(1, ChatMessage::query()->where('chat_room_id', $room->id)->count());

        $message = ChatMessage::query()->where('chat_room_id', $room->id)->firstOrFail();

        $this->assertSame($this->admin->id, $message->sender_user_id);
        $this->assertStringContainsString('Welcome on board', $message->body);
    }

    public function test_dashboard_messages_auto_selects_first_room(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\DashboardMessages::class)
            ->assertSet('activeRoomId', $room->id);
    }

    public function test_dashboard_messages_starts_with_mobile_thread_hidden_on_auto_select(): void
    {
        $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\DashboardMessages::class)
            ->assertSet('mobileThreadVisible', false);
    }

    public function test_dashboard_messages_shows_mobile_thread_when_room_selected(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\DashboardMessages::class)
            ->dispatch('room-selected', roomId: $room->id)
            ->assertSet('mobileThreadVisible', true)
            ->call('hideMobileThread')
            ->assertSet('mobileThreadVisible', false);
    }

    public function test_dashboard_messages_route_with_room_param_shows_mobile_thread(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\DashboardMessages::class, ['room' => $room->id])
            ->assertSet('mobileThreadVisible', true);
    }

    public function test_floating_chat_close_resets_panel_state(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\Chat\FloatingChat::class)
            ->call('openRoom', $room->id)
            ->set('body', 'Draft')
            ->call('closeChat')
            ->assertSet('isOpen', false)
            ->assertSet('activeRoomId', null)
            ->assertSet('body', '');
    }

    public function test_floating_chat_toggle_opens_conversation_list_not_room(): void
    {
        $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\Chat\FloatingChat::class)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->assertSet('activeRoomId', null)
            ->assertSet('body', '')
            ->assertSet('replyToMessageId', null);
    }

    public function test_floating_chat_back_returns_to_room_list(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\Chat\FloatingChat::class)
            ->call('toggle')
            ->call('openRoom', $room->id)
            ->set('body', 'Draft')
            ->call('showRooms')
            ->assertSet('isOpen', true)
            ->assertSet('activeRoomId', null)
            ->assertSet('body', '');
    }

    public function test_message_thread_send_creates_message_and_clears_input(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->actingAs($this->student, 'web');

        Livewire::test(\App\Livewire\Chat\MessageThread::class, ['roomId' => $room->id])
            ->set('body', 'Testing message send')
            ->call('send')
            ->assertSet('body', '')
            ->assertSet('replyToMessageId', null);

        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room->id,
            'sender_user_id' => $this->student->id,
            'body' => 'Testing message send',
        ]);
    }

    public function test_dashboard_messages_page_hides_floating_chat_widget(): void
    {
        $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $response = $this
            ->actingAs($this->student, 'web')
            ->get(route('dashboard.messages'));

        $response->assertOk();
        $response->assertDontSee('data-chat-action="floating-toggle"', false);
        $response->assertSee(__('web.chat.title'));
    }

    public function test_enrollment_cancellation_removes_student_from_group_room(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $enrollment = Enrollment::query()
            ->where('user_id', $this->student->id)
            ->where('course_group_id', $this->group->id)
            ->first();

        $enrollment->update(['status' => EnrollmentStatus::Cancelled]);

        $participant = ChatRoomParticipant::query()
            ->where('chat_room_id', $room->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($participant->left_at);
    }

    public function test_get_unread_count_returns_correct_count(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $this->service->sendMessage($this->trainer, $room, 'Message 1');
        $this->service->sendMessage($this->trainer, $room, 'Message 2');

        $count = $this->service->getUnreadCountForUser($this->student);

        $this->assertSame(1, $count);

        $this->service->markRoomRead($this->student, $room);

        $count = $this->service->getUnreadCountForUser($this->student);
        $this->assertSame(0, $count);
    }

    public function test_get_unread_count_ignores_hidden_messages(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);
        $message = $this->service->sendMessage($this->trainer, $room, 'Hidden message');
        $this->service->adminHideMessage($this->admin, $message, 'Moderated');

        $count = $this->service->getUnreadCountForUser($this->student);

        $this->assertSame(0, $count);
    }

    public function test_chat_livewire_polling_is_not_aggressive(): void
    {
        $paths = [
            'views/livewire/chat/room-list.blade.php',
            'views/livewire/chat/message-thread.blade.php',
            'views/livewire/chat/course-sidebar.blade.php',
            'views/livewire/chat/floating-chat.blade.php',
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents(resource_path($path));

            $this->assertStringNotContainsString('wire:poll.5s', $contents);
        }
    }

    public function test_archive_group_room_makes_room_read_only(): void
    {
        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->service->archiveGroupRoom($this->group);

        $room->refresh();
        $this->assertNotNull($room->read_only_at);
        $this->assertFalse($room->is_active);
    }

    public function test_new_chat_message_sends_mail_to_student_recipient_only(): void
    {
        Notification::fake();

        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->service->sendMessage($this->trainer, $room, 'New update in chat');

        Notification::assertSentTo(
            $this->student,
            NewChatMessageNotification::class,
            function (NewChatMessageNotification $notification, array $channels, User $notifiable): bool {
                return in_array('database', $channels, true) && in_array('mail', $channels, true);
            }
        );
    }

    public function test_new_chat_message_does_not_send_mail_to_trainer_recipient(): void
    {
        Notification::fake();

        $room = $this->service->findOrCreateGroupRoom($this->group, $this->trainer);

        $this->service->sendMessage($this->student, $room, 'Question for trainer');

        Notification::assertSentTo(
            $this->trainer,
            NewChatMessageNotification::class,
            function (NewChatMessageNotification $notification, array $channels, User $notifiable): bool {
                return in_array('database', $channels, true) && ! in_array('mail', $channels, true);
            }
        );
    }

    public function test_new_group_session_notifies_enrolled_students_via_mail(): void
    {
        Notification::fake();

        $section = CourseCurriculumSection::query()->create([
            'course_id' => $this->course->id,
            'title' => ['ar' => 'اليوم الأول', 'en' => 'Day 1'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'sort_order' => 1,
        ]);

        CourseGroupSession::query()->create([
            'course_group_id' => $this->group->id,
            'course_curriculum_section_id' => $section->id,
            'title' => 'Live Q&A',
            'start_at' => now()->addDay(),
            'duration_minutes' => 60,
            'join_url' => 'https://example.com/join',
        ]);

        Notification::assertSentTo(
            $this->student,
            NewCourseGroupContentNotification::class,
            function (NewCourseGroupContentNotification $notification, array $channels, User $notifiable): bool {
                return in_array('database', $channels, true) && in_array('mail', $channels, true);
            }
        );

        Notification::assertNotSentTo($this->trainer, NewCourseGroupContentNotification::class);
    }
}
