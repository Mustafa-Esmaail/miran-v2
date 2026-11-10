<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Livewire\Landing\LeadFormV2;
use App\Models\Category;
use App\Models\Course;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class LandingSlugSheetRowTest extends TestCase
{
    use RefreshDatabase;

    public function test_row_builder_appends_the_landing_slug_after_the_price(): void
    {
        [$request] = $this->makeRequest();

        $service = $this->app->make(GoogleSheetsService::class);

        $this->assertSame('كورس 1199 ريال', $service->buildRowForType($request)[5]);
        $this->assertSame('كورس 1199 ريال pmp-online-v2', $service->buildRowForType($request, 'pmp-online-v2')[5]);
    }

    public function test_new_landing_lead_sends_its_slug_to_the_sheet_and_is_not_stored(): void
    {
        [, $type, $course] = $this->makeRequest();

        $sheets = Mockery::mock(GoogleSheetsService::class)->makePartial();
        $sheets->shouldReceive('appendRowFromColumnA')
            ->once()
            ->withArgs(fn (string $sheetId, array $row) => $row[5] === 'كورس 1199 ريال pmp-online-v2')
            ->andReturn(true);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        Livewire::test(LeadFormV2::class, ['lockedCourseId' => $course->id, 'landingSlug' => 'pmp-online-v2'])
            ->set('typeId', $type->id)
            ->set('phone', '551234567')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_leads_without_a_slug_keep_the_plain_course_text(): void
    {
        [, $type, $course] = $this->makeRequest();

        $sheets = Mockery::mock(GoogleSheetsService::class)->makePartial();
        $sheets->shouldReceive('appendRowFromColumnA')
            ->once()
            ->withArgs(fn (string $sheetId, array $row) => $row[5] === 'كورس 1199 ريال')
            ->andReturn(true);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        Livewire::test(LeadFormV2::class, ['lockedCourseId' => $course->id])
            ->set('typeId', $type->id)
            ->set('phone', '551234568')
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_landing_slug_cannot_be_changed_from_the_browser(): void
    {
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(LeadFormV2::class, ['landingSlug' => 'pmp-online-v2'])
            ->set('landingSlug', 'evil');
    }

    /**
     * @return array{0: ServiceRequest, 1: ServiceRequestType, 2: Course}
     */
    private function makeRequest(): array
    {
        $type = ServiceRequestType::query()->updateOrCreate(
            ['slug' => 'tlbat'],
            [
                'name' => ['ar' => 'طلبات'],
                'is_active' => true,
                'sort_order' => 1,
                'sync_to_sheets' => true,
                'sheet_id' => 'sheet-123',
            ],
        );

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['ar' => 'كورس', 'en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'price' => 1199,
        ]);

        // Created without events so the observer doesn't try a real sheet sync here.
        $request = ServiceRequest::withoutEvents(fn () => ServiceRequest::query()->create([
            'source' => ServiceRequestSource::Lead,
            'type_id' => $type->id,
            'phone' => '966500000000',
            'course_id' => $course->id,
        ]));

        return [$request, $type, $course];
    }
}
