<?php

namespace Tests\Feature;

use App\Livewire\Home\LeadForm;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormCourseSelectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_hides_course_select_when_disabled_in_section_fields(): void
    {
        $section = $this->createLeadFormSection(false);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->assertDontSee('wire:model.defer="courseId"', false);
    }

    public function test_lead_form_shows_course_select_when_enabled_in_section_fields(): void
    {
        $section = $this->createLeadFormSection(true);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->assertSee('wire:model.defer="courseId"', false);
    }

    public function test_lead_form_uses_static_course_select_even_with_dynamic_course_field(): void
    {
        $section = $this->createLeadFormSection(true);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'fields',
            'type' => 'json',
            'value' => [
                'value' => [
                    [
                        'name' => 'full_name',
                        'type' => 'text',
                        'label_ar' => 'الاسم',
                        'label_en' => 'Name',
                        'required' => true,
                    ],
                    [
                        'name' => 'course',
                        'type' => 'select',
                        'label_ar' => 'الدورة',
                        'label_en' => 'Course',
                        'required' => true,
                    ],
                ],
            ],
            'sort_order' => 2,
        ]);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->assertSee('wire:model.defer="courseId"', false)
            ->assertDontSee('wire:model.defer="formData.course"', false);
    }

    private function createLeadFormSection(bool $showCourseSelect): Section
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Contact', 'ar' => 'Contact'],
            'slug' => 'lead-form-toggle-'.($showCourseSelect ? 'on' : 'off'),
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'lead_form',
            'title' => 'Lead',
            'subtitle' => '',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'layout',
            'type' => 'string',
            'value' => ['value' => 'default'],
            'sort_order' => 0,
        ]);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'show_course_select',
            'type' => 'boolean',
            'value' => ['value' => $showCourseSelect],
            'sort_order' => 1,
        ]);

        return $section;
    }
}
