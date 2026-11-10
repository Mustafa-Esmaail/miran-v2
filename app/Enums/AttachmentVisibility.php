<?php

namespace App\Enums;

enum AttachmentVisibility: string
{
    case Public = 'public';
    case EnrolledOnly = 'enrolled_only';
    case Profile = 'profile';
    case General = 'general';

    /**
     * Whether the attachment is linked to a specific group/day, so admins must pick one.
     */
    public function requiresCurriculumSection(): bool
    {
        return ! in_array($this, [self::Profile, self::General], true);
    }

    /**
     * Resolves a raw form state (string or enum) and checks whether it needs a group/day.
     */
    public static function sectionRequiredFor(mixed $state): bool
    {
        $visibility = $state instanceof self ? $state : self::tryFrom((string) $state);

        return $visibility?->requiresCurriculumSection() ?? true;
    }
}
