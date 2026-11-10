<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Livewire\Home\LeadForm;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionField;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormLandingSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_defaults_to_landing_page_source_on_a_landing_lead_form_section(): void
    {
        $section = $this->createSection('landing_lead_form', showCourseSelect: false);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->set('name', 'Test Lead')
            ->set('phone', '512345678')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ServiceRequest::query()->firstOrFail();

        $this->assertSame(ServiceRequestSource::LandingPage, $request->source);
    }

    public function test_lead_form_keeps_generic_lead_source_on_a_non_landing_section(): void
    {
        $section = $this->createSection('lead_form', showCourseSelect: false);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->set('name', 'Test Lead')
            ->set('phone', '512345679')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ServiceRequest::query()->firstOrFail();

        $this->assertSame(ServiceRequestSource::Lead, $request->source);
    }

    public function test_admin_configured_source_still_overrides_the_landing_default(): void
    {
        $section = $this->createSection('landing_lead_form', showCourseSelect: false);

        SectionField::query()->create([
            'section_id' => $section->id,
            'key' => 'source',
            'type' => 'string',
            'value' => ['value' => 'contact'],
            'sort_order' => 2,
        ]);

        Livewire::test(LeadForm::class, ['sectionId' => $section->id])
            ->set('name', 'Test Lead')
            ->set('phone', '512345680')
            ->call('submit')
            ->assertHasNoErrors();

        $request = ServiceRequest::query()->firstOrFail();

        $this->assertSame(ServiceRequestSource::Contact, $request->source);
    }

    private function createSection(string $type, bool $showCourseSelect): Section
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Landing', 'ar' => 'Landing'],
            'slug' => 'lead-form-source-'.$type.'-'.uniqid(),
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'page_id' => $page->id,
            'type' => $type,
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
