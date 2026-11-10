<?php

namespace Tests\Feature;

use Filament\Forms\Components\RichEditor;
use Tests\TestCase;

class FilamentRichEditorUploadConfigTest extends TestCase
{
    public function test_rich_editor_uploads_are_configured_for_s3_private_storage(): void
    {
        $editor = RichEditor::make('content');

        $this->assertSame('s3', $editor->getFileAttachmentsDiskName());
        $this->assertSame('course-intros', $editor->getFileAttachmentsDirectory());
        $this->assertSame('private', $editor->getFileAttachmentsVisibility());
    }
}
