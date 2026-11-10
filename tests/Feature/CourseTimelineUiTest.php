<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseTimelineUiTest extends TestCase
{
    public function test_course_resource_contains_timeline_repeater_fields(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Courses/Schemas/CourseForm.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("Repeater::make('timeline_items')", $contents);
        $this->assertStringContainsString("Repeater::make('timeline_highlights')", $contents);
        $this->assertStringContainsString("TextInput::make('title_ar')", $contents);
        $this->assertStringContainsString("TextInput::make('title_en')", $contents);
        $this->assertStringContainsString("FileUpload::make('image')", $contents);
        $this->assertStringContainsString("RichEditor::make('description_ar')", $contents);
        $this->assertStringContainsString("RichEditor::make('description_en')", $contents);
        $this->assertStringContainsString("FileUpload::make('icon')", $contents);
        $this->assertStringContainsString("asset('img/icons/image-1.png')", $contents);
    }

    public function test_course_show_about_tab_contains_timeline_markup(): void
    {
        $contents = file_get_contents(base_path('resources/views/livewire/courses/course-show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("__('web.course.timeline')", $contents);
        $this->assertStringContainsString('@foreach ($timelineItems as $item)', $contents);
        $this->assertStringContainsString('if ($timelineItems !== [])', $contents);
        $this->assertStringContainsString('@if (!empty($timelineHighlights))', $contents);
        $this->assertStringContainsString('@if (!empty($sneakImages))', $contents);
        $this->assertStringContainsString('@if (!empty($keyPoints))', $contents);
        $this->assertStringContainsString("asset('img/icons/image-'", $contents);
        $this->assertStringContainsString("{!! \$item['description'] !!}", $contents);
        $this->assertStringContainsString('wire:model.live="quantity"', $contents);
        $this->assertStringContainsString("__('web.course.accreditation_number')", $contents);
    }

    public function test_course_show_component_maps_timeline_highlights_from_course_data(): void
    {
        $contents = file_get_contents(base_path('app/Livewire/Courses/CourseShow.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('$course->timeline_highlights', $contents);
        $this->assertStringContainsString("'timelineHighlights' => \$timelineHighlights", $contents);
        $this->assertStringContainsString('$sneakImages = $course->sneak_peek_image_urls;', $contents);
        $this->assertStringNotContainsString('array_fill(0, 4, $viewModel->imageUrl)', $contents);
    }

    public function test_course_resource_contains_accreditation_number_field(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Courses/Schemas/CourseForm.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("TextInput::make('accreditation_number')", $contents);
        $this->assertStringContainsString("__('admin.labels.accreditation_number')", $contents);
    }

    public function test_course_resource_contains_free_course_toggle(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Courses/Schemas/CourseForm.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("Toggle::make('is_free')", $contents);
        $this->assertStringContainsString("__('admin.labels.is_free')", $contents);
    }

    public function test_course_resource_contains_instructors_selector(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Courses/Schemas/CourseForm.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("Select::make('trainers')", $contents);
        $this->assertStringContainsString('->relationship(', $contents);
        $this->assertStringContainsString("__('admin.labels.trainers')", $contents);
    }
}
