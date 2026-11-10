<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardCenterPageRouteTest extends TestCase
{
    public function test_dashboard_center_route_returns_the_static_html_page(): void
    {
        $response = $this->get(route('dashboard.center'));

        $response->assertOk();
        $response->assertSee('مركز لوحات التحكم', false);
        $response->assertSee('/img/Miran-logo.png', false);
    }
}
