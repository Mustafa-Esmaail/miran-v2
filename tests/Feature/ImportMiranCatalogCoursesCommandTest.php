<?php

namespace Tests\Feature;

use App\Services\MiranCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportMiranCatalogCoursesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_passes_limit_and_disables_cms_sync(): void
    {
        $importer = Mockery::mock(MiranCourseImporter::class);
        $importer->shouldReceive('import')
            ->once()
            ->with(Mockery::type('callable'), 5, false)
            ->andReturn([
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->app->instance(MiranCourseImporter::class, $importer);

        $this->artisan('miran:import-catalog-courses --n=5')
            ->assertExitCode(0);
    }
}
