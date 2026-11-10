<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\CallQualityCriteria\CallQualityCriterionResource;
use App\Filament\Admin\Resources\CallQualityCriteria\Pages\CreateCallQualityCriterion;
use App\Filament\Admin\Resources\CallQualityCriteria\Pages\EditCallQualityCriterion;
use App\Filament\Admin\Resources\CallQualityCriteria\Pages\ListCallQualityCriteria;
use App\Models\CallEvaluation;
use App\Models\CallQualityCriterion;
use App\Models\User;
use App\Support\CallQuality\CallQualityLevels;
use Database\Seeders\CallQualitySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CallQualityCriteriaResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(CallQualitySeeder::class);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->actingAs($user, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function manager(): User
    {
        return $this->userWith(['call_quality_criteria.manage']);
    }

    public function test_list_requires_the_manage_permission(): void
    {
        $this->userWith(['call_evaluations.create']);

        $this->get(CallQualityCriterionResource::getUrl('index'))->assertForbidden();
    }

    public function test_manager_sees_the_criteria_and_the_active_weight_total(): void
    {
        $this->manager();

        $this->get(CallQualityCriterionResource::getUrl('index'))->assertOk();

        Livewire::test(ListCallQualityCriteria::class)
            ->assertOk()
            ->assertCanSeeTableRecords(CallQualityCriterion::query()->ordered()->get(), inOrder: true)
            ->assertSee('97.5%');
    }

    public function test_reordering_updates_the_sort_order(): void
    {
        $this->manager();

        $ids = CallQualityCriterion::query()->ordered()->pluck('id')->all();
        $reversed = array_reverse($ids);

        Livewire::test(ListCallQualityCriteria::class)
            ->call('reorderTable', array_map('strval', $reversed));

        $this->assertSame($reversed, CallQualityCriterion::query()->ordered()->pluck('id')->all());
    }

    public function test_creating_a_criterion_gets_the_next_key_and_last_position(): void
    {
        $this->manager();

        Livewire::test(CreateCallQualityCriterion::class)
            ->fillForm([
                'name' => ['ar' => 'معيار جديد', 'en' => 'New criterion'],
                'weight' => 7.5,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $criterion = CallQualityCriterion::query()->latest('id')->firstOrFail();

        $this->assertSame('c7', $criterion->key);
        $this->assertSame('New criterion', $criterion->getTranslation('name', 'en'));
        $this->assertSame('معيار جديد', $criterion->getTranslation('name', 'ar'));
        $this->assertSame(7.5, $criterion->weight);
        $this->assertSame((int) CallQualityCriterion::query()->max('sort_order'), $criterion->sort_order);
    }

    public function test_create_validates_the_weight_range(): void
    {
        $this->manager();

        Livewire::test(CreateCallQualityCriterion::class)
            ->fillForm([
                'name' => ['ar' => 'معيار', 'en' => 'Criterion'],
                'weight' => 150,
            ])
            ->call('create')
            ->assertHasFormErrors(['weight']);
    }

    public function test_editing_a_weight_rescores_existing_evaluations(): void
    {
        $this->manager();

        $evaluation = CallEvaluation::factory()->create(['scores' => ['c1' => 5, 'c2' => 1]]);
        $criterion = CallQualityCriterion::query()->where('key', 'c2')->firstOrFail();

        Livewire::test(EditCallQualityCriterion::class, ['record' => $criterion->getRouteKey()])
            ->assertFormSet(['name' => ['ar' => 'الأسئلة الاستشارية', 'en' => 'Consultative questions']])
            ->fillForm(['weight' => 12.5])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(60.0, $evaluation->refresh()->score_percent, 0.01);
        $this->assertSame('acceptable', $evaluation->level);
    }

    public function test_thresholds_action_saves_and_relevels_evaluations(): void
    {
        $this->manager();

        $evaluation = CallEvaluation::factory()->scoredAll(4)->create();
        $this->assertSame('very_good', $evaluation->level);

        Livewire::test(ListCallQualityCriteria::class)
            ->assertActionVisible('levelThresholds')
            ->callAction('levelThresholds', [
                'excellent' => 95,
                'very_good' => 85,
                'good' => 75,
                'acceptable' => 65,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame(95, CallQualityLevels::thresholds()['excellent']);
        $this->assertSame(65, CallQualityLevels::thresholds()['acceptable']);
        $this->assertSame('good', $evaluation->refresh()->level);
    }

    public function test_thresholds_action_rejects_levels_out_of_order(): void
    {
        $this->manager();

        Livewire::test(ListCallQualityCriteria::class)
            ->callAction('levelThresholds', [
                'excellent' => 80,
                'very_good' => 85,
                'good' => 70,
                'acceptable' => 70,
            ])
            ->assertHasActionErrors(['very_good', 'acceptable']);

        $this->assertSame(CallQualityLevels::DEFAULTS['excellent'], CallQualityLevels::thresholds()['excellent']);
    }
}
