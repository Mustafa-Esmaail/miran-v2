<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\CommentReply;
use Illuminate\Support\HtmlString;

class CommentAdminPresenter
{
    public function contextHtml(Comment $comment): HtmlString
    {
        $comment->loadMissing(['user', 'commentable']);
        $context = $comment->adminContext();

        $items = [
            __('admin.labels.user') => $comment->user?->name ?? '-',
            __('admin.labels.course') => $context['course_title'],
            __('admin.labels.group') => $context['group_title'],
            __('admin.labels.type') => $context['related_type'],
            __('admin.labels.related_item') => $context['related_title'],
            __('admin.labels.created_at') => optional($comment->created_at)?->format('Y-m-d H:i') ?? '-',
        ];

        $html = '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:16px;">';

        foreach ($items as $label => $value) {
            $html .= sprintf(
                '<div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#f9fafb;"><div style="font-size:12px;color:#6b7280;margin-bottom:6px;">%s</div><div style="font-size:14px;color:#111827;font-weight:600;word-break:break-word;">%s</div></div>',
                e($label),
                e($value)
            );
        }

        $html .= '</div>';
        $html .= sprintf(
            '<div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:#ffffff;"><div style="font-size:12px;color:#6b7280;margin-bottom:6px;">%s</div><div style="font-size:14px;color:#111827;line-height:1.7;white-space:pre-wrap;">%s</div></div>',
            e(__('admin.labels.comment')),
            e($comment->content)
        );

        return new HtmlString($html);
    }

    public function historyHtml(Comment $comment, ?CommentReply $focusReply = null): HtmlString
    {
        $comment->loadMissing(['user', 'allReplies.user', 'allReplies.parentReply.user']);

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';
        $html .= $this->historyCard(
            $comment->user?->name ?? '-',
            __('admin.labels.comment'),
            $comment->content,
            optional($comment->created_at)?->format('Y-m-d H:i') ?? '-',
            false
        );

        foreach ($comment->allReplies->sortBy('created_at') as $reply) {
            $isFocusReply = $focusReply?->is($reply) ?? false;
            $label = __('admin.labels.reply');

            if ($reply->parentReply?->user?->name) {
                $label .= ' - '.__('admin.labels.parent_reply').': '.$reply->parentReply->user->name;
            }

            $html .= $this->historyCard(
                $reply->user?->name ?? '-',
                $label,
                $reply->content,
                optional($reply->created_at)?->format('Y-m-d H:i') ?? '-',
                $isFocusReply
            );
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    private function historyCard(string $user, string $label, string $content, string $createdAt, bool $highlight): string
    {
        $borderColor = $highlight ? '#3b82f6' : '#e5e7eb';
        $background = $highlight ? '#eff6ff' : '#ffffff';

        return sprintf(
            '<div style="border:1px solid %s;border-radius:12px;padding:14px;background:%s;"><div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:8px;"><div><div style="font-size:13px;color:#111827;font-weight:700;">%s</div><div style="font-size:12px;color:#6b7280;">%s</div></div><div style="font-size:12px;color:#6b7280;">%s</div></div><div style="font-size:14px;color:#111827;line-height:1.7;white-space:pre-wrap;">%s</div></div>',
            e($borderColor),
            e($background),
            e($user),
            e($label),
            e($createdAt),
            e($content)
        );
    }
}
