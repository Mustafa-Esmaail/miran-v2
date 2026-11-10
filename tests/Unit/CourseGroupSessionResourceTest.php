<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseGroupSessionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_resource_uses_badge_links_for_join_and_recording_columns(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/CourseGroupSessions/Tables/CourseGroupSessionsTable.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("TextColumn::make('join_url')", $contents);
        $this->assertStringContainsString("__('admin.actions.join')", $contents);
        $this->assertStringContainsString("TextColumn::make('recording_url')", $contents);
        $this->assertStringContainsString("__('admin.actions.watch_recording')", $contents);
        $this->assertStringContainsString('->badge()', $contents);
        $this->assertStringContainsString('->openUrlInNewTab()', $contents);
    }
}
