<?php

namespace Tests\Unit;

use Tests\TestCase;

class CommentResourceTest extends TestCase
{
    public function test_comment_resource_exposes_reply_action_for_admin_comments(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Comments/Tables/CommentsTable.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("Action::make('reply')", $contents);
        $this->assertStringContainsString('CommentService $service', $contents);
        $this->assertStringContainsString("\$service->addReply(\$record, \$user, \$data['content']);", $contents);
        $this->assertStringContainsString("__('admin.modals.comments.reply_heading')", $contents);
        $this->assertStringContainsString("__('admin.modals.comments.context_heading')", $contents);
        $this->assertStringContainsString("__('admin.modals.comments.history_heading')", $contents);
        $this->assertStringContainsString("__('admin.labels.related_item')", $contents);
    }

    public function test_comment_resource_registers_replies_relation_manager(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Comments/CommentResource.php'));
        $relationManagerContents = file_get_contents(base_path('app/Filament/Admin/Resources/Comments/RelationManagers/RepliesRelationManager.php'));

        $this->assertIsString($contents);
        $this->assertIsString($relationManagerContents);
        $this->assertStringContainsString('RepliesRelationManager::class', $contents);
        $this->assertStringContainsString("protected static string \$relationship = 'allReplies';", $relationManagerContents);
        $this->assertStringContainsString("app(CommentService::class)->addReply(\$comment, auth('admin')->user(), \$data['content'], \$record);", $relationManagerContents);
        $this->assertStringContainsString("__('admin.modals.comments.context_heading')", $relationManagerContents);
        $this->assertStringContainsString("__('admin.modals.comments.history_heading')", $relationManagerContents);
    }

    public function test_admin_translations_include_comment_reply_labels(): void
    {
        $englishTranslations = require lang_path('en/admin.php');
        $arabicTranslations = require lang_path('ar/admin.php');

        $this->assertSame('Reply to comment', data_get($englishTranslations, 'modals.comments.reply_heading'));
        $this->assertSame('Comment details', data_get($englishTranslations, 'modals.comments.context_heading'));
        $this->assertSame('Comment history', data_get($englishTranslations, 'modals.comments.history_heading'));
        $this->assertSame('Reply', data_get($englishTranslations, 'actions.reply'));
        $this->assertSame('Reply', data_get($englishTranslations, 'labels.reply'));
        $this->assertSame('Related item', data_get($englishTranslations, 'labels.related_item'));
        $this->assertSame('الرد على التعليق', data_get($arabicTranslations, 'modals.comments.reply_heading'));
        $this->assertSame('بيانات التعليق', data_get($arabicTranslations, 'modals.comments.context_heading'));
        $this->assertSame('سجل التعليق', data_get($arabicTranslations, 'modals.comments.history_heading'));
        $this->assertSame('رد', data_get($arabicTranslations, 'actions.reply'));
        $this->assertSame('الرد', data_get($arabicTranslations, 'labels.reply'));
        $this->assertSame('العنصر المرتبط', data_get($arabicTranslations, 'labels.related_item'));
    }
}
