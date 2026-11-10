<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Actions;

use App\Models\CallEvaluation;
use App\Models\User;
use App\Services\CallQualityService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * "Discussed with the employee" and "manager approved" toggles, shared by the
 * table rows and the view page. Each stamps who and when through CallQualityService.
 */
class CallEvaluationReviewActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            Action::make('mark_discussed')
                ->label(__('admin.call_quality.actions.mark_discussed'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->visible(fn (CallEvaluation $record): bool => ! $record->isDiscussed() && self::service()->canDiscuss(self::user(), $record))
                ->action(fn (CallEvaluation $record) => self::setDiscussed($record, true)),
            Action::make('undo_discussed')
                ->label(__('admin.call_quality.actions.undo_discussed'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (CallEvaluation $record): bool => $record->isDiscussed() && self::service()->canDiscuss(self::user(), $record))
                ->action(fn (CallEvaluation $record) => self::setDiscussed($record, false)),
            Action::make('approve')
                ->label(__('admin.call_quality.actions.approve'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (CallEvaluation $record): bool => ! $record->isApproved() && self::service()->canApprove(self::user()))
                ->action(fn (CallEvaluation $record) => self::setApproved($record, true)),
            Action::make('undo_approved')
                ->label(__('admin.call_quality.actions.undo_approved'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (CallEvaluation $record): bool => $record->isApproved() && self::service()->canApprove(self::user()))
                ->action(fn (CallEvaluation $record) => self::setApproved($record, false)),
        ];
    }

    private static function setDiscussed(CallEvaluation $record, bool $discussed): void
    {
        $user = self::user();

        if ($user === null || ! self::service()->canDiscuss($user, $record)) {
            return;
        }

        self::service()->setDiscussed($record, $discussed, $user);

        Notification::make()
            ->title(__('admin.call_quality.notifications.'.($discussed ? 'discussed' : 'discussed_undone')))
            ->success()
            ->send();
    }

    private static function setApproved(CallEvaluation $record, bool $approved): void
    {
        $user = self::user();

        if ($user === null || ! self::service()->canApprove($user)) {
            return;
        }

        self::service()->setApproved($record, $approved, $user);

        Notification::make()
            ->title(__('admin.call_quality.notifications.'.($approved ? 'approved' : 'approved_undone')))
            ->success()
            ->send();
    }

    private static function service(): CallQualityService
    {
        return app(CallQualityService::class);
    }

    private static function user(): ?User
    {
        $user = auth('admin')->user();

        return $user instanceof User ? $user : null;
    }
}
