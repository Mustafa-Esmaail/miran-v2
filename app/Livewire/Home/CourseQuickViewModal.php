<?php

namespace App\Livewire\Home;

use App\Livewire\Concerns\UsesSectionFields;
use App\Models\Course;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseQuickViewModal extends Component
{
    use UsesSectionFields;

    public bool $open = false;

    public ?array $course = null;

    public function mount(?int $sectionId = null): void
    {
        $this->sectionId = $sectionId;
        $this->loadSectionFields();
    }

    #[On('open-course-quick-view')]
    public function openModal(int $courseId): void
    {
        $locale = app()->getLocale();

        $course = Course::query()
            ->published()
            ->with('category')
            ->with(['groups' => function ($query) {
                $query->open()->orderBy('start_at');
            }])
            ->find($courseId);

        if (! $course) {
            return;
        }

        $nextGroup = $course->groups->first();
        $price = $course->is_free ? 0 : $course->final_price;

        $this->course = [
            'id' => $course->id,
            'title' => $course->getTranslation('title', $locale),
            'summary' => $course->getTranslation('summary', $locale),
            'slug' => $course->slug,
            'category' => $course->category?->getTranslation('name', $locale),
            'thumbnail' => $course->image_url,
            'price' => $price,
            'next_start' => optional($nextGroup?->start_at)->toDateString(),
        ];

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.home.course-quick-view-modal', [
            'quickViewTitle' => $this->fieldText('quick_view_title', $locale, __('web.home.quick_view_title')),
            'viewCourse' => $this->fieldText('view_course', $locale, __('web.home.view_course')),
        ]);
    }
}
