<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isBundle = $this->purchasable_type === Bundle::class;

        return [
            'id' => $this->id,
            'is_bundle' => $isBundle,
            'type' => Str::snake(class_basename($this->purchasable_type)),
            'title' => $this->title_snapshot,
            'bundle_title' => $this->when($isBundle, fn (): ?string => $this->arabicTitle($this->purchasable)),
            'quantity' => $this->quantity,
            'price_amount' => $this->price_amount,
            'currency' => $this->currency,
            'courses' => $this->courses(),
        ];
    }

    /**
     * One entry per course the item covers: the group itself for a course group item,
     * each selected group for a bundle (or each bundle course when no selection was saved).
     *
     * @return array<int, array{course_title: ?string, group_title: ?string, group_start_date: ?string}>
     */
    private function courses(): array
    {
        $purchasable = $this->purchasable;

        if ($purchasable instanceof CourseGroup) {
            return [$this->courseEntry($purchasable->course, $purchasable)];
        }

        if ($this->purchasable_type !== Bundle::class) {
            return [];
        }

        $selectedGroups = $this->relationLoaded('selectedCourseGroups') ? $this->selectedCourseGroups : collect();

        if ($selectedGroups->isNotEmpty()) {
            return $selectedGroups
                ->map(fn (CourseGroup $group): array => $this->courseEntry($group->course, $group))
                ->all();
        }

        return ($purchasable?->courses ?? collect())
            ->map(fn (Course $course): array => $this->courseEntry($course, null))
            ->all();
    }

    /**
     * @return array{course_title: ?string, group_title: ?string, group_start_date: ?string}
     */
    private function courseEntry(?Course $course, ?CourseGroup $group): array
    {
        return [
            'course_title' => $this->arabicTitle($course),
            'group_title' => $this->arabicTitle($group),
            'group_start_date' => $group?->start_at?->toDateString(),
        ];
    }

    /**
     * Arabic title only — no fallback to another locale.
     */
    private function arabicTitle(?Model $model): ?string
    {
        $title = $model?->getTranslation('title', 'ar', false);

        return filled($title) ? $title : null;
    }
}
