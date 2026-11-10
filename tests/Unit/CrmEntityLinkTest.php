<?php

namespace Tests\Unit;

use App\Models\Deal;
use App\Models\ServiceRequest;
use App\Support\CrmEntityLink;
use Tests\TestCase;

class CrmEntityLinkTest extends TestCase
{
    public function test_it_resolves_service_request_edit_url(): void
    {
        $this->assertSame(
            route('filament.admin.resources.service-requests.edit', 12),
            CrmEntityLink::editUrl(ServiceRequest::class, 12),
        );
    }

    public function test_it_resolves_deal_edit_url(): void
    {
        $this->assertSame(
            route('filament.admin.resources.deals.edit', 99),
            CrmEntityLink::editUrl(Deal::class, 99),
        );
    }

    public function test_it_returns_null_for_unknown_entity_type(): void
    {
        $this->assertNull(CrmEntityLink::editUrl('Unknown\\Model', 1));
    }
}
