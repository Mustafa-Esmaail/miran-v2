<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionResourceFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_group_session_resource_has_content_and_attachments_fields(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/CourseGroupSessions/Schemas/CourseGroupSessionForm.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("RichEditor::make('content')", $contents);
        $this->assertStringContainsString("FileUpload::make('attachments')", $contents);
        $this->assertStringContainsString("DateTimePicker::make('start_at')", $contents);
        $this->assertStringContainsString('->seconds(false)', $contents);
        $this->assertStringContainsString("Textarea::make('join_url')", $contents);
        $this->assertStringContainsString("Textarea::make('recording_url')", $contents);
    }

    public function test_dashboard_course_player_session_tab_renders_content_and_attachments(): void
    {
        $componentContents = file_get_contents(base_path('app/Livewire/Dashboard/CoursePlayer/Session.php'));
        $viewContents = file_get_contents(base_path('resources/views/livewire/dashboard/course-player/session.blade.php'));

        $this->assertNotFalse($componentContents);
        $this->assertNotFalse($viewContents);
        $this->assertStringContainsString("'content' => filled(\$session->content)", $componentContents);
        $this->assertStringContainsString('$attachments = collect($session->attachments ?? [])', $componentContents);
        $this->assertStringContainsString("->format('Y-m-d H:i')", $componentContents);
        $this->assertStringContainsString("\$sessionData['content']", $viewContents);
        $this->assertStringContainsString("\$sessionData['attachments']", $viewContents);
        $this->assertStringContainsString('rich-content', $viewContents);
    }

    public function test_course_group_session_accepts_long_join_and_recording_urls(): void
    {
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

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $longUrl = 'https://us06web.zoom.us/rec/play/'
            .'3hlcGSLdPgf0OlT8GMX3jRxsu2nohKF09s4WwbkZzM-HMG2HpiDSXj3A9tGtQQgYHeUFBkBGpXyXNmjk.'
            .'-tL_Z6NSczvppPrb?eagerLoadZvaPages=sidemenu.billing.plan_management&accessLevel=meeting'
            .'&canPlayFromShare=true&from=share_recording_detail&continueMode=true&oldStyle=true'
            .'&componentName=rec-play&originRequestUrl=https%3A%2F%2Fus06web.zoom.us%2Frec%2Fshare'
            .'%2FOAgBs6sr1y-mOCqGsr3xFpb4nC3dkQg6E79wyixJkjR7nQmRTJZyfHizt0cPPkEi.XbVVKWAvnYaDD9f7';

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Long link session',
            'start_at' => now()->addDay(),
            'duration_minutes' => 90,
            'join_url' => $longUrl,
            'recording_url' => $longUrl.'&mode=recording',
        ]);

        $this->assertSame($longUrl, $session->fresh()->join_url);
        $this->assertSame($longUrl.'&mode=recording', $session->fresh()->recording_url);
    }
}
