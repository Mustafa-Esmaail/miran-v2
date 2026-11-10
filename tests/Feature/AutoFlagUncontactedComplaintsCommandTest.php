<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoFlagUncontactedComplaintsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_stale_new_complaints_to_not_contacted(): void
    {
        $stale = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'new',
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('service-requests:flag-uncontacted-complaints')->assertSuccessful();

        $this->assertSame('complaint_not_contacted', $stale->fresh()->status);
    }

    public function test_it_leaves_recent_new_complaints_untouched(): void
    {
        $recent = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'new',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->artisan('service-requests:flag-uncontacted-complaints')->assertSuccessful();

        $this->assertSame('new', $recent->fresh()->status);
    }

    public function test_it_does_not_touch_stale_new_requests_from_other_sources(): void
    {
        $lead = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('service-requests:flag-uncontacted-complaints')->assertSuccessful();

        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_it_does_not_touch_stale_complaints_already_past_new(): void
    {
        $alreadyMoved = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'complaint_inquiry',
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('service-requests:flag-uncontacted-complaints')->assertSuccessful();

        $this->assertSame('complaint_inquiry', $alreadyMoved->fresh()->status);
    }
}
