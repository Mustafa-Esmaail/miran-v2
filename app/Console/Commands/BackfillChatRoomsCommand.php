<?php

namespace App\Console\Commands;

use App\Enums\ChatRoomType;
use App\Models\ChatRoom;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Console\Command;

class BackfillChatRoomsCommand extends Command
{
    protected $signature = 'chat:backfill-rooms
                            {--dry-run : Preview what would be created without persisting}';

    protected $description = 'Create group chat rooms for existing course groups that do not have one yet';

    public function handle(ChatService $chat): int
    {
        $isDry = (bool) $this->option('dry-run');

        // Groups that already have a group chat room
        $existingGroupIds = ChatRoom::query()
            ->where('type', ChatRoomType::CourseGroup)
            ->pluck('course_group_id')
            ->all();

        $groups = CourseGroup::query()
            ->whereNotIn('id', $existingGroupIds)
            ->with('course.trainers')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('All course groups already have chat rooms. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d group(s) without a chat room.',
            $isDry ? '[DRY-RUN] Would create rooms for' : 'Creating rooms for',
            $groups->count(),
        ));

        // Resolve a fallback actor (any admin)
        $admin = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']))
            ->first();

        foreach ($groups as $group) {
            $actor = $group->course->trainers->first() ?? $admin;

            if (! $actor) {
                $this->warn("  ⚠  Group #{$group->id} — no instructor or admin found, skipping.");

                continue;
            }

            $title = $group->getTranslation('title', 'ar')
                ?: $group->getTranslation('title', 'en')
                ?: "Group #{$group->id}";

            if ($isDry) {
                $this->line("  → Would create room for group #{$group->id}: {$title}");

                continue;
            }

            $chat->findOrCreateGroupRoom($group, $actor);

            $this->line("  ✓  Created room for group #{$group->id}: {$title}");
        }

        if (! $isDry) {
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}
