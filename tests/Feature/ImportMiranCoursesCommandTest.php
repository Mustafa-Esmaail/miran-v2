<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\MiranCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportMiranCoursesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_passes_limit_option(): void
    {
        $importer = Mockery::mock(MiranCourseImporter::class);
        $importer->shouldReceive('import')
            ->once()
            ->with(Mockery::type('callable'), 3)
            ->andReturn([
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->app->instance(MiranCourseImporter::class, $importer);

        $this->artisan('miran:import-courses --n=3')
            ->assertExitCode(0);
    }

    public function test_seed_only_option_seeds_catalog_and_skips_remote_import(): void
    {
        $importer = Mockery::mock(MiranCourseImporter::class);
        $importer->shouldNotReceive('import');
        $this->app->instance(MiranCourseImporter::class, $importer);

        $this->artisan('miran:import-courses --seed-only')
            ->assertExitCode(0);

        $this->assertSame(10, Course::query()->count());
    }
}
