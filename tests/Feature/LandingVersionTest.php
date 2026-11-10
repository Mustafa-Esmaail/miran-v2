<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Exceptions\LandingVersionException;
use App\Exports\ReportArrayExport;
use App\Filament\Pages\LandingVersionReport;
use App\Livewire\Landing\LeadFormV2;
use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Services\LandingVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LandingVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_landing_page_starts_as_v1_of_its_own_line(): void
    {
        $page = $this->createLanding('pmpon');

        $this->assertSame('pmpon', $page->landing_key);
        $this->assertSame(1, $page->version);
        $this->assertNotNull($page->published_at);
        $this->assertTrue($page->isLiveVersion());
    }

    public function test_non_landing_pages_are_not_versioned(): void
    {
        $page = $this->createLanding('about-us', 'about');

        $this->assertNull($page->landing_key);
        $this->assertFalse($page->isVersioned());
    }

    public function test_publishing_a_new_version_moves_the_live_page_to_draft(): void
    {
        $live = $this->createLanding('pmpon');
        $replacement = $this->createLanding('pmpon-new');

        $published = app(LandingVersionService::class)->publishNewVersion($live, $replacement, 'November offer', 'snap');

        $live->refresh();
        $this->assertSame('pmpon-v1', $live->slug);
        $this->assertSame('pmpon', $live->landing_key);
        $this->assertFalse($live->is_active);
        $this->assertNotNull($live->drafted_at);

        $this->assertSame('pmpon', $published->slug);
        $this->assertSame('pmpon', $published->landing_key);
        $this->assertSame(2, $published->version);
        $this->assertSame('November offer', $published->versionLabel());
        $this->assertSame('snap', $published->marketing_source);
        $this->assertTrue($published->isLiveVersion());

        $this->withoutVite();
        $this->get('/pmpon')->assertOk()->assertSee('Landing pmpon-new');
        $this->get('/pmpon-v1')->assertNotFound();
    }

    public function test_versions_keep_counting_up_on_the_same_slug(): void
    {
        $service = app(LandingVersionService::class);
        $v2 = $service->publishNewVersion($this->createLanding('pmpon'), $this->createLanding('pmpon-b'));
        $v3 = $service->publishNewVersion($v2, $this->createLanding('pmpon-c'));

        $this->assertSame(3, $v3->version);
        $this->assertSame('pmpon', $v3->slug);
        $this->assertSame('v3', $v3->versionLabel());
        $this->assertSame('pmpon-v2', $v2->refresh()->slug);
        $this->assertSame(
            ['pmpon', 'pmpon-v1', 'pmpon-v2'],
            Page::query()->where('landing_key', 'pmpon')->orderBy('slug')->pluck('slug')->all(),
        );
    }

    public function test_renaming_a_live_page_moves_its_landing_line(): void
    {
        $page = $this->createLanding('temp-slug');

        $page->update(['slug' => 'final-slug']);

        $this->assertSame('final-slug', $page->refresh()->landing_key);
        $this->assertTrue($page->isLiveVersion());
    }

    public function test_it_refuses_a_draft_slug_that_is_already_taken(): void
    {
        $live = $this->createLanding('pmpon');
        $this->createLanding('pmpon-v1', 'about');

        $this->expectException(LandingVersionException::class);

        app(LandingVersionService::class)->publishNewVersion($live, $this->createLanding('pmpon-new'));
    }

    public function test_it_refuses_a_page_from_another_version_line(): void
    {
        $service = app(LandingVersionService::class);
        $otherLive = $service->publishNewVersion($this->createLanding('kpi'), $this->createLanding('kpi-new'));

        $this->expectException(LandingVersionException::class);

        $service->publishNewVersion($this->createLanding('pmpon'), $otherLive);
    }

    public function test_it_refuses_to_replace_a_drafted_version(): void
    {
        $service = app(LandingVersionService::class);
        $live = $this->createLanding('pmpon');
        $service->publishNewVersion($live, $this->createLanding('pmpon-new'));

        $this->expectException(LandingVersionException::class);

        $service->publishNewVersion($live->refresh(), $this->createLanding('pmpon-other'));
    }

    public function test_replacement_options_only_list_standalone_landing_pages(): void
    {
        $service = app(LandingVersionService::class);
        $live = $this->createLanding('pmpon');
        $candidate = $this->createLanding('pmpon-new');
        $service->publishNewVersion($this->createLanding('kpi'), $this->createLanding('kpi-new'));
        $this->createLanding('about-us', 'about');

        $this->assertSame([$candidate->id], array_keys($service->replacementOptions($live, 'en')));
    }

    public function test_drafted_version_is_previewable_by_admins_only(): void
    {
        $live = $this->createLanding('pmpon');
        app(LandingVersionService::class)->publishNewVersion($live, $this->createLanding('pmpon-new'));

        $this->withoutVite();
        $this->get('/admin/landing-previews/pmpon-v1')->assertRedirect();

        $this->actingAs(User::factory()->create(), 'admin');
        $this->get(route('admin.pages.preview', $live->refresh()))
            ->assertOk()
            ->assertSee('Landing pmpon');
        $this->assertStringEndsWith('/admin/landing-previews/pmpon-v1', route('admin.pages.preview', $live));
    }

    public function test_lead_form_stores_the_landing_page_and_it_is_locked(): void
    {
        $page = $this->createLanding('pmpon');

        Livewire::test(LeadFormV2::class, ['pageId' => $page->id])
            ->set('phone', '551234567')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($page->id, ServiceRequest::query()->firstOrFail()->page_id);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(LeadFormV2::class, ['pageId' => $page->id])->set('pageId', 999);
    }

    public function test_sheet_row_adds_the_version_after_the_slug(): void
    {
        $type = ServiceRequestType::query()->updateOrCreate(['slug' => 'tlbat'], ['name' => ['ar' => 'طلبات'], 'is_active' => true, 'sort_order' => 1]);
        $live = $this->createLanding('pmpon');
        $v2 = app(LandingVersionService::class)->publishNewVersion($live, $this->createLanding('pmpon-new'));

        $request = ServiceRequest::withoutEvents(fn () => ServiceRequest::query()->create([
            'source' => ServiceRequestSource::Lead,
            'type_id' => $type->id,
            'phone' => '966500000000',
            'page_id' => $v2->id,
        ]));

        $this->assertSame('pmpon v2', app(GoogleSheetsService::class)->buildRowForType($request, 'pmpon')[5]);
    }

    public function test_report_lists_each_version_with_its_b2c_leads(): void
    {
        $this->actingAsAdminWithPermissions(['landing_version_report.viewAny']);
        $course = $this->createCourse();
        $live = $this->createLanding('pmpon', 'landing-v2', $course);
        $this->createLead($live, ServiceRequestSource::Lead);
        $this->createLead($live, ServiceRequestSource::Lead);
        $this->createLead($live, ServiceRequestSource::Complaint);
        $v2 = app(LandingVersionService::class)->publishNewVersion($live, $this->createLanding('pmpon-new', 'landing-v2', $course), null, 'google');
        $this->createLead($v2, ServiceRequestSource::Lead);
        $this->createLead(null, ServiceRequestSource::Lead);

        $totals = Livewire::test(LandingVersionReport::class)->instance()->getReportTotals();
        $this->assertSame(2, $totals['versions_count']);
        $this->assertSame(3, $totals['leads_total']);

        Livewire::test(LandingVersionReport::class)
            ->assertOk()
            ->assertSee('/admin/landing-previews/pmpon-v1', false)
            ->assertSee('google')
            ->assertSee('Course');

        Excel::fake();
        Excel::matchByRegex();

        Livewire::test(LandingVersionReport::class)->callAction('export_xlsx');

        Excel::assertDownloaded('/^miran-landing-version-report-\d{8}-\d{6}\.xlsx$/', function (ReportArrayExport $export): bool {
            $rows = $export->array();

            return count($export->headings()) === 8
                && $rows[0][2] === 'v2' && $rows[0][1] === __('admin.landing_versions.labels.live') && $rows[0][7] === 1
                && $rows[1][2] === 'v1' && $rows[1][7] === 2 && str_ends_with($rows[1][6], '/pmpon')
                && end($rows)[7] === 3;
        });
    }

    public function test_report_exports_csv_and_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['landing_version_report.viewAny']);
        $live = $this->createLanding('pmpon', 'landing-v2', $this->createCourse());
        $this->createLead($live, ServiceRequestSource::Lead);

        Livewire::test(LandingVersionReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(LandingVersionReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    public function test_report_shows_the_totals_strip(): void
    {
        $this->actingAsAdminWithPermissions(['landing_version_report.viewAny']);
        $live = $this->createLanding('pmpon', 'landing-v2', $this->createCourse());
        $this->createLead($live, ServiceRequestSource::Lead);

        Livewire::test(LandingVersionReport::class)
            ->assertOk()
            ->assertSee('fi-report-filters', false)
            ->assertSee('fi-report-totals', false)
            ->assertSee(__('admin.landing_versions.labels.versions_count'));
    }

    public function test_report_and_action_need_permissions(): void
    {
        User::factory()->create();

        $this->assertFalse(LandingVersionReport::canAccess());
    }

    private function createLanding(string $slug, string $template = 'landing-v2', ?Course $course = null): Page
    {
        return Page::query()->create([
            'title' => ['en' => 'Landing '.$slug, 'ar' => 'Landing '.$slug],
            'slug' => $slug,
            'body' => ['en' => '', 'ar' => ''],
            'template' => $template,
            'landable_type' => $course ? Course::class : null,
            'landable_id' => $course?->id,
            'is_published' => true,
            'is_active' => true,
        ]);
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course', 'ar' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);
    }

    private function createLead(?Page $page, ServiceRequestSource $source): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'source' => $source,
            'page_id' => $page?->id,
        ]);
    }

    private function actingAsAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }
}
