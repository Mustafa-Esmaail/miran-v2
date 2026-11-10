<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Admin\ServiceRequestImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceRequestImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'name,email,phone,source,status,priority,employee_code,assigned_to_email,message';

    /** @var list<string> */
    private array $writtenFiles = [];

    protected function tearDown(): void
    {
        Storage::disk('local')->delete($this->writtenFiles);

        parent::tearDown();
    }

    /**
     * @param  list<string>  $lines
     */
    private function writeCsv(array $lines): string
    {
        $relativePath = 'testing/lead-import-'.uniqid().'.csv';
        Storage::disk('local')->put($relativePath, implode("\n", [self::HEADER, ...$lines])."\n");
        $this->writtenFiles[] = $relativePath;

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * New, duplicate in file, duplicate of an existing lead (stored as "0552222222", written
     * in the file as "00966 55 222 2222"), and two error rows.
     */
    private function mixedCsv(): string
    {
        ServiceRequest::factory()->create(['phone' => '0552222222']);

        return $this->writeCsv([
            'New Lead,new@example.com,0551111111,,,,,,First',
            'Same Phone,,+966551111111,,,,,,Second',
            'Existing Lead,,00966 55 222 2222,,,,,,Third',
            ',,0553333333,,,,,,No name',
            'Unknown Employee,,0554444444,,,,NOPE-1,,Bad code',
        ]);
    }

    private function service(): ServiceRequestImportService
    {
        return app(ServiceRequestImportService::class);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsAdminWith(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    public function test_analyse_labels_new_duplicate_and_error_rows(): void
    {
        $path = $this->mixedCsv();

        $analysis = $this->service()->analyse($path);

        $this->assertSame(5, $analysis['counts']['total']);
        $this->assertSame(1, $analysis['counts']['new']);
        $this->assertSame(2, $analysis['counts']['duplicates']);
        $this->assertSame(1, $analysis['counts']['duplicates_in_file']);
        $this->assertSame(1, $analysis['counts']['duplicates_existing']);
        $this->assertSame(2, $analysis['counts']['errors']);

        $statusesByRow = array_column($analysis['rows'], 'status', 'row');
        $this->assertSame([
            2 => ServiceRequestImportService::STATUS_NEW,
            3 => ServiceRequestImportService::STATUS_DUPLICATE_IN_FILE,
            4 => ServiceRequestImportService::STATUS_DUPLICATE_EXISTING,
            5 => ServiceRequestImportService::STATUS_ERROR,
            6 => ServiceRequestImportService::STATUS_ERROR,
        ], $statusesByRow);

        $this->assertSame('966551111111', $analysis['rows'][0]['data']['phone']);
        $this->assertNotEmpty($analysis['rows'][4]['reason']);
    }

    public function test_analyse_writes_nothing(): void
    {
        $path = $this->mixedCsv();
        $before = ServiceRequest::query()->count();

        $this->service()->analyse($path);

        $this->assertSame($before, ServiceRequest::query()->count());
    }

    public function test_import_skips_duplicates_by_default(): void
    {
        $path = $this->mixedCsv();
        $before = ServiceRequest::query()->count();

        $summary = $this->service()->import($path);

        $this->assertSame(5, $summary['processed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(2, $summary['duplicates']);
        $this->assertSame(2, $summary['duplicates_skipped']);
        $this->assertSame(4, $summary['skipped']);
        $this->assertCount(2, $summary['errors']);
        $this->assertSame($before + 1, ServiceRequest::query()->count());
        $this->assertDatabaseHas('service_requests', ['name' => 'New Lead', 'phone' => '966551111111']);
        $this->assertDatabaseMissing('service_requests', ['name' => 'Same Phone']);
        $this->assertDatabaseMissing('service_requests', ['name' => 'Existing Lead']);
    }

    public function test_import_keeps_duplicates_when_asked(): void
    {
        $path = $this->mixedCsv();
        $before = ServiceRequest::query()->count();

        $summary = $this->service()->import($path, skipDuplicates: false);

        $this->assertSame(3, $summary['created']);
        $this->assertSame(0, $summary['duplicates_skipped']);
        $this->assertSame(2, $summary['skipped']);
        $this->assertSame($before + 3, ServiceRequest::query()->count());
        $this->assertDatabaseHas('service_requests', ['name' => 'Same Phone']);
        $this->assertDatabaseHas('service_requests', ['name' => 'Existing Lead']);
    }

    public function test_employee_code_sets_assignee_and_team(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'employee_code' => 'EMP-777']);

        $path = $this->writeCsv(['Coded Lead,,0556666666,,,,EMP-777,,']);

        $analysis = $this->service()->analyse($path);
        $this->assertSame(ServiceRequestImportService::STATUS_NEW, $analysis['rows'][0]['status']);

        $this->service()->import($path);

        $lead = ServiceRequest::query()->where('name', 'Coded Lead')->firstOrFail();
        $this->assertSame($user->id, (int) $lead->assigned_to);
        $this->assertSame($employee->team_id, (int) $lead->team_id);
    }

    public function test_employee_code_wins_over_email_and_email_is_the_fallback(): void
    {
        $codedUser = User::factory()->create();
        $coded = Employee::factory()->create(['user_id' => $codedUser->id, 'employee_code' => 'EMP-100']);
        $emailUser = User::factory()->create(['email' => 'seller@example.com']);
        $byEmail = Employee::factory()->create(['user_id' => $emailUser->id, 'employee_code' => 'EMP-200']);

        $path = $this->writeCsv([
            'Both Given,,0557000001,,,,EMP-100,seller@example.com,',
            'Email Only,,0557000002,,,,,seller@example.com,',
        ]);

        $this->service()->import($path);

        $both = ServiceRequest::query()->where('name', 'Both Given')->firstOrFail();
        $this->assertSame($codedUser->id, (int) $both->assigned_to);
        $this->assertSame($coded->team_id, (int) $both->team_id);

        $emailOnly = ServiceRequest::query()->where('name', 'Email Only')->firstOrFail();
        $this->assertSame($emailUser->id, (int) $emailOnly->assigned_to);
        $this->assertSame($byEmail->team_id, (int) $emailOnly->team_id);
    }

    public function test_unknown_employee_code_or_email_is_an_error_row(): void
    {
        $path = $this->writeCsv([
            'Bad Code,,0558000001,,,,NOPE-9,,',
            'Bad Email,,0558000002,,,,,nobody@example.com,',
        ]);

        $analysis = $this->service()->analyse($path);

        $this->assertSame(2, $analysis['counts']['errors']);
        $this->assertSame(0, $analysis['counts']['new']);

        $summary = $this->service()->import($path);

        $this->assertSame(0, $summary['created']);
        $this->assertDatabaseMissing('service_requests', ['name' => 'Bad Code']);
        $this->assertDatabaseMissing('service_requests', ['name' => 'Bad Email']);
    }

    public function test_import_action_on_list_page_imports_new_rows_and_skips_duplicates(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'service_requests.create']);
        ServiceRequest::factory()->create(['phone' => '0552222222']);

        $csv = implode("\n", [
            self::HEADER,
            'Action Lead,,0559100001,,,,,,',
            'Action Existing,,+966552222222,,,,,,',
        ])."\n";

        Livewire::test(ListServiceRequests::class)
            ->callAction('import_service_requests', data: [
                'file' => UploadedFile::fake()->createWithContent('leads.csv', $csv),
                'skip_duplicates' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('service_requests', ['name' => 'Action Lead', 'phone' => '966559100001']);
        $this->assertDatabaseMissing('service_requests', ['name' => 'Action Existing']);
    }
}
