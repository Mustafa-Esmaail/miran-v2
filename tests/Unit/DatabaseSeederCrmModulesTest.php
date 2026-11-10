<?php

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseSeederCrmModulesTest extends TestCase
{
    public function test_database_seeder_calls_service_request_seeders(): void
    {
        $contents = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('ServiceRequestSeeder::class', $contents);
        $this->assertStringContainsString('ServiceRequestActivitySeeder::class', $contents);
    }
}
