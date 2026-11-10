<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Departments\DepartmentResource;
use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\ComplaintServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestActivityResource;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use App\Filament\Admin\Resources\Teams\TeamResource;
use Tests\TestCase;

class CrmNavigationGroupingTest extends TestCase
{
    public function test_service_request_resources_use_crm_navigation_group(): void
    {
        $this->assertSame(__('admin.nav_groups.crm'), ServiceRequestResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), ServiceRequestActivityResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), ComplaintServiceRequestResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), B2bServiceRequestResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), B2cServiceRequestResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), DepartmentResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), TeamResource::getNavigationGroup());
        $this->assertSame(__('admin.nav_groups.crm'), EmployeeResource::getNavigationGroup());
    }
}
