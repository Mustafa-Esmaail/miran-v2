<?php

namespace Tests\Unit;

use App\Filament\Widgets\LatestCommentsWidget;
use Tests\TestCase;

class LatestCommentsWidgetTest extends TestCase
{
    public function test_widget_uses_comments_query_and_translation_heading(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Widgets/LatestCommentsWidget.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('Comment::applyAdminContextEagerLoading(Comment::query())', $contents);
        $this->assertStringContainsString('->limit(5)', $contents);
        $this->assertStringContainsString("Action::make('reply')", $contents);
        $this->assertStringContainsString("Action::make('edit')", $contents);
        $this->assertStringContainsString("Action::make('view_all')", $contents);
        $this->assertStringContainsString("CommentResource::getUrl('edit', ['record' => \$record])", $contents);
        $this->assertStringContainsString("__('admin.modals.comments.context_heading')", $contents);
        $this->assertStringContainsString("__('admin.modals.comments.history_heading')", $contents);
        $this->assertStringContainsString("__('admin.dashboard_widgets.queues.latest_comments.heading')", $contents);
        $this->assertSame(__('admin.dashboard_widgets.queues.latest_comments.heading'), (new LatestCommentsWidget)->getHeading());
    }
}
