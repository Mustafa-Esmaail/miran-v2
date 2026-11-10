<?php

namespace Tests\Feature;

use Tests\TestCase;

class CommentThreadNestedRepliesTest extends TestCase
{
    public function test_comment_thread_supports_replying_to_replies(): void
    {
        $componentContents = file_get_contents(base_path('app/Livewire/Comments/CommentThread.php'));
        $threadViewContents = file_get_contents(base_path('resources/views/livewire/comments/comment-thread.blade.php'));
        $replyPartialContents = file_get_contents(base_path('resources/views/livewire/comments/partials/reply-item.blade.php'));

        $this->assertIsString($componentContents);
        $this->assertIsString($threadViewContents);
        $this->assertIsString($replyPartialContents);
        $this->assertStringContainsString('submitReplyToReply', $componentContents);
        $this->assertStringContainsString('buildReplyTree', $componentContents);
        $this->assertStringContainsString("@include('livewire.comments.partials.reply-item'", $threadViewContents);
        $this->assertStringContainsString('wire:submit.prevent="submitReplyToReply({{ $comment->id }}, {{ $reply->id }})"', $replyPartialContents);
        $this->assertStringContainsString('@if ($reply->childReplies->isNotEmpty())', $replyPartialContents);
    }
}
