<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\User;
use App\Repositories\Contracts\CommentReplyRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly CommentReplyRepositoryInterface $replies,
        private readonly NotificationService $notifications
    ) {}

    public function addComment(object $commentable, User $user, string $content): Comment
    {
        $autoApprove = $this->shouldAutoApprove($commentable);

        $comment = $this->comments->create([
            'commentable_id' => $commentable->id,
            'commentable_type' => $commentable::class,
            'user_id' => $user->id,
            'content' => $content,
            'approved' => $autoApprove,
            'hidden' => false,
        ]);

        $this->notifications->notifyNewComment($comment);

        return $comment;
    }

    public function addReply(Comment $comment, User $user, string $content, ?CommentReply $parentReply = null): CommentReply
    {
        $autoApprove = $this->shouldAutoApproveType($comment->commentable_type);

        $reply = $this->replies->create([
            'comment_id' => $comment->id,
            'parent_reply_id' => $parentReply?->id,
            'user_id' => $user->id,
            'content' => $content,
            'approved' => $autoApprove,
            'hidden' => false,
        ]);

        $this->notifications->notifyNewCommentReply($comment, $reply);

        return $reply;
    }

    public function queryFor(object $commentable, ?User $viewer = null): Builder
    {
        $query = $this->comments->query()
            ->where('commentable_type', $commentable::class)
            ->where('commentable_id', $commentable->id)
            ->with('user')
            ->with(['allReplies' => function ($builder) use ($viewer) {
                $builder->with('user')
                    ->when($viewer, fn (Builder $replyBuilder) => $this->applyReplyVisibilityForViewer($replyBuilder, $viewer))
                    ->when(! $viewer, fn (Builder $replyBuilder) => $replyBuilder->where('approved', true)->where('hidden', false))
                    ->latest();
            }])
            ->latest();

        return $query->when($viewer, function (Builder $builder) use ($viewer) {
            $builder->where(function (Builder $visibility) use ($viewer) {
                $visibility->where('approved', true)
                    ->where('hidden', false)
                    ->orWhere('user_id', $viewer->id);
            });
        }, function (Builder $builder) {
            $builder->where('approved', true)->where('hidden', false);
        });
    }

    public function buildReplyTree(Collection $replies): Collection
    {
        $childrenByParent = $replies
            ->groupBy(fn (CommentReply $reply) => $reply->parent_reply_id ? (string) $reply->parent_reply_id : 'root')
            ->map(fn (Collection $group) => $group->sortByDesc('created_at')->values());

        $attachChildren = function (CommentReply $reply) use (&$attachChildren, $childrenByParent): CommentReply {
            $children = $childrenByParent->get((string) $reply->id, collect())
                ->map(fn (CommentReply $childReply) => $attachChildren($childReply))
                ->values();

            $reply->setRelation('childReplies', $children);

            return $reply;
        };

        return $childrenByParent->get('root', collect())
            ->map(fn (CommentReply $reply) => $attachChildren($reply))
            ->values();
    }

    public function resolveCourse(object $commentable): ?Course
    {
        if ($commentable instanceof Course) {
            return $commentable;
        }

        if ($commentable instanceof Lesson) {
            return $commentable->course;
        }

        if ($commentable instanceof CourseAttachment) {
            return $commentable->course;
        }

        if ($commentable instanceof CourseGroupSession) {
            return $commentable->courseGroup?->course;
        }

        if ($commentable instanceof Page) {
            return null;
        }

        return null;
    }

    private function shouldAutoApprove(object $commentable): bool
    {
        return $commentable instanceof Course
            || $commentable instanceof Lesson
            || $commentable instanceof CourseAttachment
            || $commentable instanceof CourseGroupSession;
    }

    private function shouldAutoApproveType(string $commentableType): bool
    {
        return in_array($commentableType, [
            Course::class,
            Lesson::class,
            CourseAttachment::class,
            CourseGroupSession::class,
        ], true);
    }

    private function applyReplyVisibilityForViewer(Builder $query, User $viewer): Builder
    {
        return $query->where(function (Builder $visibility) use ($viewer) {
            $visibility->where('approved', true)
                ->where('hidden', false)
                ->orWhere('user_id', $viewer->id);
        });
    }
}
