<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptureReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_code_matches_regardless_of_case(): void
    {
        $employee = Employee::factory()->create([
            'employee_code' => 'ABC123',
            'is_active' => true,
        ]);

        $this->get('/?source=abc123');

        $this->assertSame($employee->id, session('referral_employee_id'));
    }

    public function test_unknown_referral_code_does_not_set_session(): void
    {
        $this->get('/?source=doesnotexist');

        $this->assertNull(session('referral_employee_id'));
    }

    public function test_unknown_referral_code_is_captured_as_marketing_source(): void
    {
        $this->get('/?source=SNPC_DT_Excel_IMG_PUR_15OFF');

        $this->assertNull(session('referral_employee_id'));
        $this->assertSame('SNPC_DT_Excel_IMG_PUR_15OFF', session('marketing_source'));
    }

    public function test_matched_employee_code_is_also_captured_as_marketing_source(): void
    {
        $employee = Employee::factory()->create([
            'employee_code' => 'ABC123',
            'is_active' => true,
        ]);

        $this->get('/?source=abc123');

        $this->assertSame($employee->id, session('referral_employee_id'));
        $this->assertSame('abc123', session('marketing_source'));
    }
}
