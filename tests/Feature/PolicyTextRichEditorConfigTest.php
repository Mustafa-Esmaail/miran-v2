<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Page\RelationManagers\SectionsRelationManager;
use App\Filament\Admin\Resources\Sections\Schemas\SectionForm;
use Filament\Forms\Components\RichEditor;
use ReflectionMethod;
use Tests\TestCase;

class PolicyTextRichEditorConfigTest extends TestCase
{
    public function test_section_resource_policy_text_uses_private_s3_uploads(): void
    {
        $method = new ReflectionMethod(SectionForm::class, 'policyTextFields');
        $method->setAccessible(true);
        $fields = $method->invoke(null);

        $this->assertPolicyTextEditorsUsePrivateS3($fields);
    }

    public function test_sections_relation_manager_policy_text_uses_private_s3_uploads(): void
    {
        $manager = new class extends SectionsRelationManager
        {
            public function exposePolicyTextFields(): array
            {
                return $this->policyTextFields();
            }
        };

        $fields = $manager->exposePolicyTextFields();

        $this->assertPolicyTextEditorsUsePrivateS3($fields);
    }

    private function assertPolicyTextEditorsUsePrivateS3(array $fields): void
    {
        $this->assertCount(2, $fields);

        foreach ($fields as $field) {
            $this->assertInstanceOf(RichEditor::class, $field);
            $this->assertSame('s3', $field->getFileAttachmentsDiskName());
            $this->assertSame('course-intros', $field->getFileAttachmentsDirectory());
            $this->assertSame('private', $field->getFileAttachmentsVisibility());
        }
    }
}
