<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Admin\Resources\Deals\Pages\ListDeals;
use Filament\Resources\Pages\Page;
use ReflectionClass;
use Tests\TestCase;

class CrmKanbanFiltersTest extends TestCase
{
    public function test_deals_resource_uses_custom_kanban_page_view(): void
    {
        $reflection = new ReflectionClass(ListDeals::class);

        $this->assertTrue($reflection->isSubclassOf(Page::class));
        $this->assertSame(
            'filament.resources.deals.kanban.board',
            $reflection->getDefaultProperties()['view'],
        );
        $this->assertTrue($reflection->hasMethod('onStatusChanged'));
    }

    public function test_crm_tasks_resource_uses_custom_kanban_page_view(): void
    {
        $reflection = new ReflectionClass(ListCrmTasks::class);

        $this->assertTrue($reflection->isSubclassOf(Page::class));
        $this->assertSame(
            'filament.resources.crm-tasks.kanban.board',
            $reflection->getDefaultProperties()['view'],
        );
        $this->assertTrue($reflection->hasMethod('onStatusChanged'));
    }
}
