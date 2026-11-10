<?php

namespace Tests\Unit;

use App\Models\AssignmentRule;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\WorkflowRule;
use App\Policies\AssignmentRulePolicy;
use App\Policies\CrmActivityPolicy;
use App\Policies\CrmTaskPolicy;
use App\Policies\DealPolicy;
use App\Policies\WorkflowRulePolicy;
use App\Providers\AuthServiceProvider;
use ReflectionClass;
use Tests\TestCase;

class AuthPolicyMappingsTest extends TestCase
{
    public function test_crm_policy_mappings_are_registered_in_auth_service_provider(): void
    {
        $reflection = new ReflectionClass(AuthServiceProvider::class);
        $policies = $reflection->getDefaultProperties()['policies'];

        $this->assertSame(DealPolicy::class, $policies[Deal::class] ?? null);
        $this->assertSame(CrmTaskPolicy::class, $policies[CrmTask::class] ?? null);
        $this->assertSame(CrmActivityPolicy::class, $policies[CrmActivity::class] ?? null);
        $this->assertSame(AssignmentRulePolicy::class, $policies[AssignmentRule::class] ?? null);
        $this->assertSame(WorkflowRulePolicy::class, $policies[WorkflowRule::class] ?? null);
    }
}
