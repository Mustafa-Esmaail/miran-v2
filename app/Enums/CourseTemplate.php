<?php

namespace App\Enums;

enum CourseTemplate: string
{
    case Course = 'course';
    case Bootcamp = 'bootcamp';
    case BootcampV2 = 'bootcamp_v2';

    public function label(): string
    {
        return __('admin.bootcamp.templates.'.$this->value);
    }

    /**
     * Bootcamp designs share the same admin content, curriculum fields and page data.
     */
    public function isBootcamp(): bool
    {
        return in_array($this, self::bootcamps(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function bootcamps(): array
    {
        return [self::Bootcamp, self::BootcampV2];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $template): array => [$template->value => $template->label()])
            ->all();
    }
}
