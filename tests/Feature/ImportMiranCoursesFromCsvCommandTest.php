<?php

namespace Tests\Feature;

use App\Services\MiranCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportMiranCoursesFromCsvCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_uses_default_csv_file_when_option_missing(): void
    {
        $expectedFile = base_path('Miran courses Sheet.csv');

        $importer = Mockery::mock(MiranCourseImporter::class);
        $importer->shouldReceive('importFromCsv')
            ->once()
            ->with($expectedFile, Mockery::type('callable'))
            ->andReturn([
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->app->instance(MiranCourseImporter::class, $importer);

        $this->artisan('miran:import-courses-csv')
            ->assertExitCode(0);
    }
}
