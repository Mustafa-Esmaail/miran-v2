<?php

namespace Tests\Unit;

use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    public function test_filament_resources_use_descriptive_duplicate_email_validation_message(): void
    {
        $resourceFiles = [
            'app/Filament/Admin/Resources/Users/Schemas/UserForm.php',
            'app/Filament/Admin/Resources/Trainers/Schemas/TrainerForm.php',
            'app/Filament/Admin/Resources/Students/Schemas/StudentForm.php',
            'app/Filament/Admin/Resources/Employees/Schemas/EmployeeForm.php',
        ];

        foreach ($resourceFiles as $resourceFile) {
            $contents = file_get_contents(base_path($resourceFile));

            $this->assertIsString($contents);
            $this->assertStringContainsString('->unique(ignoreRecord: true)', $contents, $resourceFile);
            $this->assertStringContainsString("__('admin.validation.email_unique')", $contents, $resourceFile);
        }
    }

    public function test_admin_translation_files_define_duplicate_email_message(): void
    {
        $englishTranslations = file_get_contents(lang_path('en/admin.php'));
        $arabicTranslations = file_get_contents(lang_path('ar/admin.php'));

        $this->assertIsString($englishTranslations);
        $this->assertIsString($arabicTranslations);
        $this->assertStringContainsString("'email_unique' => 'This email is already registered.", $englishTranslations);
        $this->assertStringContainsString("'email_unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.", $arabicTranslations);
        $this->assertStringContainsString("'title' => 'Input error'", $englishTranslations);
        $this->assertStringContainsString("'body' => 'The operation was stopped because some entered data is invalid.", $englishTranslations);
        $this->assertStringContainsString("'title' => 'خطأ في الإدخال'", $arabicTranslations);
        $this->assertStringContainsString("'body' => 'تم إيقاف العملية لأن بعض البيانات المدخلة غير صحيحة.", $arabicTranslations);
    }

    public function test_filament_admin_panel_reports_validation_errors_with_descriptive_notification(): void
    {
        $providerContents = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));

        $this->assertIsString($providerContents);
        $this->assertStringContainsString('BasePage::$reportValidationErrorUsing', $providerContents);
        $this->assertStringContainsString("->title(__('admin.validation.title'))", $providerContents);
        $this->assertStringContainsString("->body(\$messages !== '' ? __('admin.validation.body').\"\\n\".\$messages : __('admin.validation.body'))", $providerContents);
    }
}
