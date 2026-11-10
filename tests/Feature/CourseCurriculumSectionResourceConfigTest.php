<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\CourseCurriculumSections\CourseCurriculumSectionResource;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\LessonsRelationManager;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\SessionsRelationManager;
use Tests\TestCase;

class CourseCurriculumSectionResourceConfigTest extends TestCase
{
    public function test_course_curriculum_section_resource_uses_day_labels(): void
    {
        $this->assertSame(__('admin.navigation.course_curriculum_sections'), CourseCurriculumSectionResource::getNavigationLabel());
        $this->assertSame(__('admin.models.course_curriculum_section'), CourseCurriculumSectionResource::getModelLabel());
        $this->assertSame(__('admin.models.course_curriculum_sections'), CourseCurriculumSectionResource::getPluralModelLabel());
    }

    public function test_course_curriculum_section_form_is_configured_as_single_column_layout(): void
    {
        $formContents = file_get_contents(base_path('app/Filament/Admin/Resources/CourseCurriculumSections/Schemas/CourseCurriculumSectionForm.php'));

        $this->assertIsString($formContents);
        $this->assertStringContainsString('->columns(1)', $formContents);
        $this->assertStringContainsString('->columnSpanFull()', $formContents);
    }

    public function test_course_curriculum_section_relation_managers_include_header_actions(): void
    {
        $relationManagers = [
            LessonsRelationManager::class => 'LessonsRelationManager.php',
            SessionsRelationManager::class => 'SessionsRelationManager.php',
            AttachmentsRelationManager::class => 'AttachmentsRelationManager.php',
        ];

        foreach ($relationManagers as $relationManager => $fileName) {
            $this->assertContains($relationManager, CourseCurriculumSectionResource::getRelations());

            $contents = file_get_contents(base_path('app/Filament/Admin/Resources/CourseCurriculumSections/RelationManagers/'.$fileName));

            $this->assertIsString($contents);
            $this->assertStringContainsString('->headerActions([', $contents);
            $this->assertStringContainsString("Action::make('create')", $contents);
            $this->assertStringContainsString("Action::make('view')", $contents);
        }
    }
}
