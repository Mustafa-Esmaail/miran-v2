<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Courses\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Courses\RelationManagers\QuizzesRelationManager;
use Tests\TestCase;

class CourseResourceRelationsTest extends TestCase
{
    public function test_course_resource_includes_quizzes_relation_manager(): void
    {
        $this->assertContains(QuizzesRelationManager::class, CourseResource::getRelations());
    }

    public function test_course_relation_managers_expose_create_actions(): void
    {
        $files = [
            'app/Filament/Admin/Resources/Courses/RelationManagers/AttachmentsRelationManager.php',
            'app/Filament/Admin/Resources/Courses/RelationManagers/CurriculumSectionsRelationManager.php',
            'app/Filament/Admin/Resources/Courses/RelationManagers/GroupsRelationManager.php',
            'app/Filament/Admin/Resources/Courses/RelationManagers/LessonsRelationManager.php',
            'app/Filament/Admin/Resources/Courses/RelationManagers/QuizzesRelationManager.php',
            'app/Filament/Admin/Resources/Courses/RelationManagers/SessionsRelationManager.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('CreateAction::make()', $contents, $file);
            $this->assertStringContainsString('headerActions([', $contents, $file);
        }
    }

    public function test_enrollments_relation_manager_handles_enum_status_values(): void
    {
        $contents = file_get_contents((new \ReflectionClass(EnrollmentsRelationManager::class))->getFileName());

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('$state instanceof EnrollmentStatus ? $state->value : (string) $state', $contents);
        $this->assertStringNotContainsString('->color(fn (string $state): string', $contents);
    }
}
