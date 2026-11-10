<?php

namespace App\Filament\Widgets;

use App\Enums\LessonType;
use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Lessons\LessonResource;
use App\Filament\Admin\Resources\Menus\MenuResource;
use App\Filament\Admin\Resources\Page\PageResource;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Lesson;
use App\Models\Menu;
use App\Models\Page;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class DataHealthChecksWidget extends Widget
{
    protected string $view = 'filament.widgets.data-health-checks-widget';

    protected int|string|array $columnSpan = 'full';

    public function getChecks(): array
    {
        return [
            [
                'title' => __('admin.dashboard_widgets.health_checks.courses_missing_sku'),
                'total' => Course::query()
                    ->where(function (Builder $query): void {
                        $query->whereNull('sku')
                            ->orWhere('sku', '')
                            ->orWhere('price', 0);
                    })
                    ->count(),
                'items' => Course::query()
                    ->where(function (Builder $query): void {
                        $query->whereNull('sku')
                            ->orWhere('sku', '')
                            ->orWhere('price', 0);
                    })
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Course $course): array => [
                        'label' => sprintf('Course #%d - %s', $course->id, $course->slug ?? 'no-slug'),
                        'url' => CourseResource::getUrl('edit', ['record' => $course]),
                    ])
                    ->all(),
            ],
            [
                'title' => __('admin.dashboard_widgets.health_checks.course_groups_over_capacity'),
                'total' => CourseGroup::query()
                    ->withCount('enrollments')
                    ->whereNotNull('capacity')
                    ->havingRaw('enrollments_count > capacity')
                    ->count(),
                'items' => CourseGroup::query()
                    ->withCount('enrollments')
                    ->whereNotNull('capacity')
                    ->havingRaw('enrollments_count > capacity')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (CourseGroup $group): array => [
                        'label' => sprintf('Group #%d - %d/%d enrolled', $group->id, $group->enrollments_count, (int) $group->capacity),
                        'url' => CourseGroupResource::getUrl('edit', ['record' => $group]),
                    ])
                    ->all(),
            ],
            [
                'title' => __('admin.dashboard_widgets.health_checks.lessons_missing_media'),
                'total' => Lesson::query()
                    ->where(function (Builder $query): void {
                        $query->where(function (Builder $builder): void {
                            $builder->where('type', LessonType::Video)
                                ->where(function (Builder $videoQuery): void {
                                    $videoQuery->whereNull('video_url')->orWhere('video_url', '');
                                });
                        })->orWhere(function (Builder $builder): void {
                            $builder->where('type', LessonType::Pdf)
                                ->where(function (Builder $pdfQuery): void {
                                    $pdfQuery->whereNull('pdf_path')->orWhere('pdf_path', '');
                                });
                        });
                    })
                    ->count(),
                'items' => Lesson::query()
                    ->where(function (Builder $query): void {
                        $query->where(function (Builder $builder): void {
                            $builder->where('type', LessonType::Video)
                                ->where(function (Builder $videoQuery): void {
                                    $videoQuery->whereNull('video_url')->orWhere('video_url', '');
                                });
                        })->orWhere(function (Builder $builder): void {
                            $builder->where('type', LessonType::Pdf)
                                ->where(function (Builder $pdfQuery): void {
                                    $pdfQuery->whereNull('pdf_path')->orWhere('pdf_path', '');
                                });
                        });
                    })
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Lesson $lesson): array => [
                        'label' => sprintf('Lesson #%d - %s', $lesson->id, $lesson->type->value),
                        'url' => LessonResource::getUrl('edit', ['record' => $lesson]),
                    ])
                    ->all(),
            ],
            [
                'title' => __('admin.dashboard_widgets.health_checks.menus_without_items'),
                'total' => Menu::query()
                    ->withCount(['items as active_items_count' => fn (Builder $query) => $query->where('is_active', true)])
                    ->where('is_active', true)
                    ->having('active_items_count', '=', 0)
                    ->count(),
                'items' => Menu::query()
                    ->withCount(['items as active_items_count' => fn (Builder $query) => $query->where('is_active', true)])
                    ->where('is_active', true)
                    ->having('active_items_count', '=', 0)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Menu $menu): array => [
                        'label' => sprintf('Menu #%d - %s', $menu->id, $menu->name),
                        'url' => MenuResource::getUrl('edit', ['record' => $menu]),
                    ])
                    ->all(),
            ],
            [
                'title' => __('admin.dashboard_widgets.health_checks.pages_without_sections'),
                'total' => Page::query()
                    ->withCount('sections')
                    ->where('is_published', true)
                    ->having('sections_count', '=', 0)
                    ->count(),
                'items' => Page::query()
                    ->withCount('sections')
                    ->where('is_published', true)
                    ->having('sections_count', '=', 0)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Page $page): array => [
                        'label' => sprintf('Page #%d - %s', $page->id, $page->slug),
                        'url' => PageResource::getUrl('edit', ['record' => $page]),
                    ])
                    ->all(),
            ],
        ];
    }
}
