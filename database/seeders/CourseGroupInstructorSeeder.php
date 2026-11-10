<?php

namespace Database\Seeders;

use App\Enums\CourseGroupStatus;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CourseGroupInstructorSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/course_group_instructors.tsv');

        if (! is_file($path)) {
            $this->command?->error("Data file not found: {$path}");

            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle, 0, "\t");

        $processed = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            [$dateStr, $instructorName, $url] = array_pad(array_slice($row, 0, 3), 3, null);

            $dateStr = trim((string) $dateStr);
            $instructorName = trim((string) $instructorName);
            $url = trim((string) $url);

            if ($dateStr === '' || $url === '') {
                $skipped++;

                continue;
            }

            $slug = $this->slugFromUrl($url);

            if (! $slug) {
                $skipped++;

                continue;
            }

            $course = Course::query()
                ->where('slug', $slug)
                ->orWhere('slug', rawurldecode($slug))
                ->first();

            if (! $course) {
                $this->command?->warn("Course not found for slug [{$slug}] (url: {$url})");
                $skipped++;

                continue;
            }

            try {
                $date = Carbon::parse($dateStr)->startOfDay();
            } catch (\Throwable) {
                $this->command?->warn("Invalid date [{$dateStr}] for slug [{$slug}]");
                $skipped++;

                continue;
            }

            $group = CourseGroup::query()
                ->where('course_id', $course->id)
                ->whereDate('start_at', $date->toDateString())
                ->first();

            if (! $group) {
                $group = CourseGroup::create([
                    'course_id' => $course->id,
                    'title' => $date->format('Y-m-d'),
                    'start_at' => $date,
                    'status' => CourseGroupStatus::Open,
                ]);
            }

            if ($instructorName !== '') {
                $instructor = User::query()->firstOrCreate(
                    ['name' => $instructorName],
                    [
                        'email' => null,
                        'password' => Hash::make(Str::random(24)),
                    ]
                );

                if (! $instructor->hasRole('instructor')) {
                    $instructor->assignRole(Role::findOrCreate('instructor', 'web'));
                }

                $group->instructors()->syncWithoutDetaching([
                    $instructor->id => ['course_id' => $course->id],
                ]);
            }

            $processed++;
        }

        fclose($handle);

        $this->command?->info("Done. Processed: {$processed}, skipped: {$skipped}.");
    }

    private function slugFromUrl(string $value): ?string
    {
        $value = rtrim(trim($value), '/');

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '://')) {
            $value = trim((string) parse_url($value, PHP_URL_PATH), '/');
        }

        if ($value === '') {
            return null;
        }

        $segments = array_values(array_filter(
            explode('/', $value),
            fn (string $segment): bool => $segment !== ''
        ));

        return $segments === [] ? null : end($segments);
    }
}
