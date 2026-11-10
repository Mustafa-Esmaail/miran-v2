<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ServiceRequestSpamSheetSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeSyncableType(string $slug = 'contact-sheet-sync-test'): ServiceRequestType
    {
        return ServiceRequestType::query()->updateOrCreate(['slug' => $slug], [
            'name' => ['en' => 'Contact'],
            'sync_to_sheets' => true,
            'sheet_id' => 'test-sheet-id',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_spam_flagged_request_does_not_sync_to_sheet(): void
    {
        $type = $this->makeSyncableType();

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldNotReceive('buildRowForType');
        $sheets->shouldNotReceive('appendRow');
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $request = ServiceRequest::factory()->create([
            'type_id' => $type->id,
            'status' => ServiceRequestStatus::spamBucket()->slug,
        ]);

        $this->assertNull($request->fresh()->sheet_synced_at);
    }

    public function test_non_spam_request_still_syncs_to_sheet(): void
    {
        $type = $this->makeSyncableType();

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('buildRowForType')->once()->andReturn(['a']);
        $sheets->shouldReceive('appendRow')->once()->with('test-sheet-id', ['a'])->andReturn(true);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $request = ServiceRequest::factory()->create([
            'type_id' => $type->id,
            'status' => ServiceRequestStatus::default()->slug,
        ]);

        $this->assertNotNull($request->fresh()->sheet_synced_at);
    }

    public function test_tlbat_request_writes_from_column_a(): void
    {
        $type = $this->makeSyncableType('tlbat');

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('buildRowForType')->once()->andReturn(['a']);
        $sheets->shouldReceive('appendRowFromColumnA')->once()->with('test-sheet-id', ['a'])->andReturn(true);
        $sheets->shouldNotReceive('appendRow');
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $request = ServiceRequest::factory()->create([
            'type_id' => $type->id,
            'status' => ServiceRequestStatus::default()->slug,
        ]);

        $this->assertNotNull($request->fresh()->sheet_synced_at);
    }

    public function test_complaint_request_keeps_using_append(): void
    {
        $type = $this->makeSyncableType('shakawi');

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('buildRowForType')->once()->andReturn(['a']);
        $sheets->shouldReceive('appendRow')->once()->with('test-sheet-id', ['a'])->andReturn(true);
        $sheets->shouldNotReceive('appendRowFromColumnA');
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $request = ServiceRequest::factory()->create([
            'type_id' => $type->id,
            'status' => ServiceRequestStatus::default()->slug,
        ]);

        $this->assertNotNull($request->fresh()->sheet_synced_at);
    }
}
