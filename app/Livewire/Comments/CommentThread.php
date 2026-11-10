<?php

namespace App\Livewire\Comments;

use App\Enums\EnrollmentStatus;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Page;
use App\Services\CommentService;
use Illuminate\View\View;
use Livewire\Component;

class CommentThread extends Component
{
    public string $commentableType;

    public int $commentableId;

    public string $commentContent = '';

    public array $replyContent = [];

    public ?int $editingCommentId = null;

    public ?int $editingReplyId = null;

    public string $editingContent = '';

    public function mount(string $commentableType, int $commentableId): void
    {
        $this->commentableType = $commentableType;
        $this->commentableId = $commentableId;
    }

    public function submitComment(CommentService $comments): void
    {
        $this->ensureAccess();

        $this->validate([
            'commentContent' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $commentable = $this->getCommentable();

        $comments->addComment($commentable, auth()->user(), $this->commentContent);

        $this->reset('commentContent');
        session()->flash('status', __('web.comments.submitted'));
        $this->dispatch('toast', message: __('web.comments.submitted'));
    }

    public function submitReplyToReply(CommentService $comments, int $commentId, int $parentReplyId): void
    {
        $this->submitReplyToTarget($comments, $commentId, $parentReplyId);
    }

    public function submitReply(CommentService $comments, int $commentId): void
    {
        $this->submitReplyToTarget($comments, $commentId, null);
    }

    private function submitReplyToTarget(CommentService $comments, int $commentId, ?int $parentReplyId): void
    {
        $this->ensureAccess();

        $replyKey = $this->replyKey($commentId, $parentReplyId);
        $content = trim($this->replyContent[$replyKey] ?? '');
        if ($content === '') {
            $this->addError("replyContent.$replyKey", __('web.comments.reply_required'));

            return;
        }

        $comment = Comment::query()->find($commentId);
        if (! $comment || ! $this->isSameCommentable($comment)) {
            $this->addError("replyContent.$replyKey", __('web.comments.invalid_comment'));

            return;
        }

        $parentReply = null;

        if ($parentReplyId) {
            $parentReply = CommentReply::query()
                ->where('comment_id', $comment->id)
                ->find($parentReplyId);

            if (! $parentReply) {
                $this->addError("replyContent.$replyKey", __('web.comments.invalid_comment'));

                return;
            }
        }

        $comments->addReply($comment, auth()->user(), $content, $parentReply);

        $this->replyContent[$replyKey] = '';
        session()->flash('status', __('web.comments.reply_submitted'));
        $this->dispatch('toast', message: __('web.comments.reply_submitted'));
    }

    public function startEditComment(int $commentId): void
    {
        $comment = Comment::query()->find($commentId);
        if (! $comment || $comment->user_id !== auth()->id() || ! $this->isSameCommentable($comment)) {
            return;
        }

        $this->editingCommentId = $commentId;
        $this->editingReplyId = null;
        $this->editingContent = $comment->content;
    }

    public function startEditReply(int $replyId): void
    {
        $reply = CommentReply::query()->find($replyId);
        if (! $reply || $reply->user_id !== auth()->id() || ! $this->isSameCommentable($reply->comment)) {
            return;
        }

        $this->editingReplyId = $replyId;
        $this->editingCommentId = null;
        $this->editingContent = $reply->content;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingContent' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        if ($this->editingCommentId) {
            $comment = Comment::query()->find($this->editingCommentId);
            if (! $comment || $comment->user_id !== auth()->id() || ! $this->isSameCommentable($comment)) {
                return;
            }

            $comment->forceFill(['content' => $this->editingContent])->save();
        }

        if ($this->editingReplyId) {
            $reply = CommentReply::query()->find($this->editingReplyId);
            if (! $reply || $reply->user_id !== auth()->id() || ! $this->isSameCommentable($reply->comment)) {
                return;
            }

            $reply->forceFill(['content' => $this->editingContent])->save();
        }

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->editingCommentId = null;
        $this->editingReplyId = null;
        $this->editingContent = '';
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::query()->find($commentId);
        if (! $comment || $comment->user_id !== auth()->id() || ! $this->isSameCommentable($comment)) {
            return;
        }

        $comment->delete();
    }

    public function deleteReply(int $replyId): void
    {
        $reply = CommentReply::query()->find($replyId);
        if (! $reply || $reply->user_id !== auth()->id() || ! $this->isSameCommentable($reply->comment)) {
            return;
        }

        $reply->delete();
    }

    public function render(CommentService $comments): View
    {
        $commentable = $this->getCommentable();
        $this->ensureAccess();

        $items = $comments->queryFor($commentable, auth()->user())->get();
        $items = $items->map(function (Comment $comment) use ($comments) {
            $comment->setRelation('replies', $comments->buildReplyTree($comment->allReplies));

            return $comment;
        });

        return view('livewire.comments.comment-thread', [
            'commentable' => $commentable,
            'comments' => $items,
            'canComment' => auth()->check(),
        ]);
    }

    private function ensureAccess(): void
    {
        if (! auth()->check()) {
            abort(403);
        }

        $commentable = $this->getCommentable();
        $requiresEnrollment = $commentable instanceof Course
            || $commentable instanceof Lesson
            || $commentable instanceof CourseAttachment
            || $commentable instanceof CourseGroupSession;

        if (! $requiresEnrollment) {
            return;
        }

        $courseId = match (true) {
            $commentable instanceof Course => $commentable->id,
            $commentable instanceof Lesson => $commentable->course_id,
            $commentable instanceof CourseAttachment => $commentable->course_id,
            $commentable instanceof CourseGroupSession => $commentable->courseGroup?->course_id,
            default => null,
        };

        if (! $courseId) {
            abort(403);
        }

        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $courseId)
            ->where('status', EnrollmentStatus::Active)
            ->first();

        abort_unless($enrollment, 403);
    }

    private function getCommentable(): object
    {
        $class = $this->resolveCommentableClass($this->commentableType);

        $commentable = $class::query()->find($this->commentableId);
        abort_unless($commentable, 404);

        return $commentable;
    }

    private function resolveCommentableClass(string $type): string
    {
        $map = [
            'course' => Course::class,
            'lesson' => Lesson::class,
            'course_attachment' => CourseAttachment::class,
            'course_group_session' => CourseGroupSession::class,
            'attachment' => CourseAttachment::class,
            'session' => CourseGroupSession::class,
            'page' => Page::class,
            Course::class => Course::class,
            Lesson::class => Lesson::class,
            CourseAttachment::class => CourseAttachment::class,
            CourseGroupSession::class => CourseGroupSession::class,
            Page::class => Page::class,
        ];

        return $map[$type] ?? abort(404);
    }

    private function isSameCommentable(Comment $comment): bool
    {
        return $comment->commentable_type === $this->resolveCommentableClass($this->commentableType)
            && $comment->commentable_id === $this->commentableId;
    }

    private function replyKey(int $commentId, ?int $parentReplyId = null): string
    {
        return $parentReplyId ? "reply-{$commentId}-{$parentReplyId}" : "comment-{$commentId}";
    }
}
