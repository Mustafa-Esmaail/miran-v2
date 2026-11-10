<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\StudentCredentialsNotification;
use App\Services\Admin\StudentImportService;
use App\Services\StudentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_students_from_csv_and_creates_enrollments(): void
    {
        Notification::fake();
        Role::findOrCreate('student', 'web');
        [$course, $group] = $this->createCourseGroup();
        $csvPath = storage_path('framework/testing/student-import.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'w');
        fputcsv($handle, app(StudentImportService::class)->templateHeaders());
        fputcsv($handle, [
            'Student One',
            'student-one@example.com',
            '0500000001',
            '1234567890',
            'Saudi',
            '2000-01-01',
            'male',
            'Imported row',
            'Password123!',
            $course->id,
            $group->id,
        ]);
        fclose($handle);

        $summary = app(StudentImportService::class)->import($csvPath);

        $student = User::query()->where('email', 'student-one@example.com')->firstOrFail();

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(1, $summary['enrolled']);
        $this->assertSame(1, $summary['mail_sent']);
        $this->assertSame(0, $summary['mail_failed']);
        $this->assertSame([], $summary['mail_errors']);
        $this->assertTrue($student->hasRole('student'));
        Notification::assertSentTo($student, StudentCredentialsNotification::class);
        $this->assertDatabaseHas((new Enrollment)->getTable(), [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
        ]);

        File::delete($csvPath);
    }

    public function test_it_updates_existing_students_and_skips_duplicate_enrollments(): void
    {
        Notification::fake();
        Role::findOrCreate('student', 'web');
        [$course, $group] = $this->createCourseGroup();
        $existing = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'student-two@example.com',
            'phone' => '0500000000',
        ]);
        $existing->assignRole('student');
        $service = app(\App\Services\StudentCreationService::class);
        $service->createOrEnroll([
            'name' => 'Existing Student',
            'email' => $existing->email,
        ], $course->id, $group->id);

        $csvPath = storage_path('framework/testing/student-import-existing.csv');
        File::ensureDirectoryExists(dirname($csvPath));
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, app(StudentImportService::class)->templateHeaders());
        fputcsv($handle, [
            'Updated Name',
            'student-two@example.com',
            '0500000099',
            '',
            '',
            '',
            '',
            '',
            'UpdatedPass123!',
            $course->id,
            $group->id,
        ]);
        fclose($handle);

        $summary = app(StudentImportService::class)->import($csvPath);

        $existing->refresh();

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['updated']);
        $this->assertSame(0, $summary['enrolled']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(1, $summary['mail_sent']);
        $this->assertSame(0, $summary['mail_failed']);
        $this->assertSame([], $summary['mail_errors']);
        $this->assertSame('Updated Name', $existing->name);
        $this->assertSame('0500000099', $existing->phone);
        Notification::assertSentTo($existing, StudentCredentialsNotification::class);
        $this->assertSame(1, Enrollment::query()->where('user_id', $existing->id)->count());

        File::delete($csvPath);
    }

    public function test_it_tracks_mail_failures_in_import_summary(): void
    {
        Role::findOrCreate('student', 'web');
        [$course, $group] = $this->createCourseGroup();
        $csvPath = storage_path('framework/testing/student-import-mail-failure.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'w');
        fputcsv($handle, app(StudentImportService::class)->templateHeaders());
        fputcsv($handle, [
            'Student Three',
            'student-three@example.com',
            '0500000003',
            '',
            '',
            '',
            '',
            '',
            'Password123!',
            $course->id,
            $group->id,
        ]);
        fclose($handle);

        $students = \Mockery::mock(StudentCreationService::class);
        $students->shouldReceive('createOrEnroll')->once()->andReturn([
            'user' => User::factory()->make(['email' => 'student-three@example.com']),
            'created' => true,
            'enrolled' => true,
            'mail_attempted' => true,
            'mail_sent' => false,
            'mail_error' => 'SMTP authentication failed.',
        ]);
        $this->app->instance(StudentCreationService::class, $students);

        $summary = app(StudentImportService::class)->import($csvPath);

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(1, $summary['enrolled']);
        $this->assertSame(0, $summary['mail_sent']);
        $this->assertSame(1, $summary['mail_failed']);
        $this->assertCount(1, $summary['mail_errors']);
        $this->assertStringContainsString('SMTP authentication failed.', $summary['mail_errors'][0]);

        File::delete($csvPath);
    }

    public function test_it_imports_arabic_names_from_windows_encoded_csv(): void
    {
        Notification::fake();
        Role::findOrCreate('student', 'web');
        [$course, $group] = $this->createCourseGroup();
        $csvPath = storage_path('framework/testing/student-import-arabic-cp1256.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, app(StudentImportService::class)->templateHeaders());
        fputcsv($stream, [
            'محمد أحمد',
            'arabic-student@example.com',
            '0500000004',
            '',
            'سعودي',
            '2000-01-01',
            'male',
            'نبذة عربية',
            'Password123!',
            $course->id,
            $group->id,
        ]);
        rewind($stream);

        $utf8Contents = stream_get_contents($stream);
        fclose($stream);

        file_put_contents($csvPath, iconv('UTF-8', 'Windows-1256//TRANSLIT', (string) $utf8Contents));

        $summary = app(StudentImportService::class)->import($csvPath);
        $student = User::query()->where('email', 'arabic-student@example.com')->firstOrFail();

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame('محمد أحمد', $student->name);
        $this->assertSame('سعودي', $student->nationality);
        $this->assertSame('نبذة عربية', $student->about);

        File::delete($csvPath);
    }

    /**
     * @return array{0: Course, 1: CourseGroup}
     */
    private function createCourseGroup(): array
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-import',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Import Course'],
            'slug' => 'import-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Import Group'],
            'status' => 'open',
        ]);

        return [$course, $group];
    }
}
