<?php

namespace Tests\Feature;

use Database\Seeders\CrmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_seeder_populates_crm_core_data(): void
    {
        $this->seed(CrmSeeder::class);

        $this->assertDatabaseCount('departments', 3);
        $this->assertDatabaseHas('teams', ['code' => 'TEAM-SALES-CORP']);
        $this->assertDatabaseHas('employees', ['email' => 'crm.sales.lead@example.com']);
        $this->assertDatabaseHas('service_requests', ['email' => 'crm.customer@example.com']);
        $this->assertDatabaseHas('service_request_activities', ['type' => 'created']);
    }
}
