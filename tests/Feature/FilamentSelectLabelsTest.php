<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilamentSelectLabelsTest extends TestCase
{
    public function test_course_related_selects_use_titles_or_names_instead_of_slug_only(): void
    {
        $courseResource = file_get_contents(base_path('app/Filament/Admin/Resources/Courses/Schemas/CourseForm.php'));
        $bundleResource = file_get_contents(base_path('app/Filament/Admin/Resources/Bundles/Schemas/BundleForm.php'));
        $courseAttachmentResource = file_get_contents(base_path('app/Filament/Admin/Resources/CourseAttachments/CourseAttachmentResource.php'));
        $courseDiscountResource = file_get_contents(base_path('app/Filament/Admin/Resources/CourseDiscounts/Schemas/CourseDiscountForm.php'));
        $courseGroupResource = file_get_contents(base_path('app/Filament/Admin/Resources/CourseGroups/Schemas/CourseGroupForm.php'));
        $lessonResource = file_get_contents(base_path('app/Filament/Admin/Resources/Lessons/Schemas/LessonForm.php'));
        $quizResource = file_get_contents(base_path('app/Filament/Admin/Resources/Quizzes/Schemas/QuizForm.php'));
        $enrollmentResource = file_get_contents(base_path('app/Filament/Admin/Resources/Enrollments/EnrollmentResource.php'));

        $this->assertNotFalse($courseResource);
        $this->assertNotFalse($bundleResource);
        $this->assertNotFalse($courseAttachmentResource);
        $this->assertNotFalse($courseDiscountResource);
        $this->assertNotFalse($courseGroupResource);
        $this->assertNotFalse($lessonResource);
        $this->assertNotFalse($quizResource);
        $this->assertNotFalse($enrollmentResource);

        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Category \$record): string => \$record->getTranslation('name', app()->getLocale()))", $courseResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Category \$record): string => \$record->getTranslation('name', app()->getLocale()))", $bundleResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Course \$record): string => \$record->getTranslation('title', app()->getLocale()))", $courseAttachmentResource);
        $this->assertStringContainsString("->searchable(['title->en', 'title->ar', 'slug'])", $courseDiscountResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Course \$record): string => \$record->getTranslation('title', app()->getLocale()))", $courseGroupResource);
        $this->assertStringContainsString("->searchable(['name->en', 'name->ar', 'slug'])", $courseResource);
        $this->assertStringNotContainsString("Select::make('category_id')\n                        ->relationship('category', 'slug')\n                        ->getOptionLabelFromRecordUsing(fn (Category \$record): string => \$record->getTranslation('name', app()->getLocale()))\n                        ->label(__('admin.labels.category'))\n                        ->required()\n                        ->preload()\n                        ->native()", $courseResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Course \$record): string => \$record->getTranslation('title', app()->getLocale()))", $lessonResource);
        $this->assertStringContainsString("->searchable(['title->en', 'title->ar', 'slug'])", $lessonResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Course \$record): string => \$record->getTranslation('title', app()->getLocale()))", $quizResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (User \$record): string => trim(\$record->name.' - '.\$record->email, ' -'))", $enrollmentResource);
    }

    public function test_menu_and_section_selects_use_readable_labels(): void
    {
        $courseGroupSessionResource = file_get_contents(base_path('app/Filament/Admin/Resources/CourseGroupSessions/Schemas/CourseGroupSessionForm.php'));
        $menuItemResource = file_get_contents(base_path('app/Filament/Admin/Resources/MenuItems/Schemas/MenuItemForm.php'));
        $menuRelationManager = file_get_contents(base_path('app/Filament/Admin/Resources/Menus/RelationManagers/MenuItemsRelationManager.php'));
        $sectionFieldResource = file_get_contents(base_path('app/Filament/Admin/Resources/SectionFields/Schemas/SectionFieldForm.php'));

        $this->assertNotFalse($courseGroupSessionResource);
        $this->assertNotFalse($menuItemResource);
        $this->assertNotFalse($menuRelationManager);
        $this->assertNotFalse($sectionFieldResource);

        $this->assertStringContainsString("getTranslation('title', app()->getLocale()) ?? ('Group #'.\$group->id)", $courseGroupSessionResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Page \$record): string => \$record->getTranslation('title', app()->getLocale()))", $menuItemResource);
        $this->assertStringContainsString("->searchable(['title->en', 'title->ar', 'slug'])", $menuItemResource);
        $this->assertStringContainsString("getOptionLabelFromRecordUsing(fn (Page \$record): string => \$record->getTranslation('title', app()->getLocale()))", $menuRelationManager);
        $this->assertStringContainsString('$title = data_get($record->title, app()->getLocale())', $sectionFieldResource);
    }
}
