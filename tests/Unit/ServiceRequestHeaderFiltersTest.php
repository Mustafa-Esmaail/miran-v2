<?php

namespace Tests\Unit;

use Tests\TestCase;

class ServiceRequestHeaderFiltersTest extends TestCase
{
    public function test_service_requests_table_has_assignee_filter(): void
    {
        $contents = file_get_contents(app_path('Filament/Admin/Resources/ServiceRequests/Tables/ServiceRequestsTable.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("SelectFilter::make('assigned_to')", $contents);
        $this->assertStringContainsString("->relationship('assignedTo', 'name')", $contents);
        $this->assertStringContainsString('->filters([', $contents);
    }

    public function test_service_request_activities_table_has_header_filters(): void
    {
        $contents = file_get_contents(app_path('Filament/Admin/Resources/ServiceRequests/Tables/ServiceRequestActivitiesTable.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString("SelectFilter::make('service_request_id')", $contents);
        $this->assertStringContainsString("SelectFilter::make('type')", $contents);
        $this->assertStringContainsString("SelectFilter::make('actor_user_id')", $contents);
        $this->assertStringContainsString("->relationship('actor', 'name')", $contents);
    }
}
