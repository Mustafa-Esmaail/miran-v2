<?php

namespace Database\Seeders;

use App\Enums\AssignmentStrategy;
use App\Enums\CommissionLedgerStatus;
use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use App\Enums\DealStage;
use App\Enums\EmployeeRole;
use App\Enums\QuoteStatus;
use App\Enums\SalesTargetPeriodType;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowTrigger;
use App\Models\AssignmentRule;
use App\Models\CommissionLedger;
use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Quote;
use App\Models\SalesTarget;
use App\Models\ServiceRequest;
use App\Models\WorkflowRule;
use Illuminate\Database\Seeder;

class CrmModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEmployeeRoles();
        $this->seedSalesTargets();
        $this->seedCommissionLedgers();
        $this->seedCrmActivitiesAndTasks();
        $this->seedCompaniesDealsQuotes();
        $this->seedDealActivitiesAndTasks();
        $this->seedAssignmentRules();
        $this->seedWorkflowRules();
    }

    // ── Phase 1 — Employee roles ──────────────────────────────────────────────

    private function seedEmployeeRoles(): void
    {
        $roleMap = [
            'crm.sales.lead@example.com' => [EmployeeRole::TeamLead,    8.00],
            'crm.sales.agent@example.com' => [EmployeeRole::SalesPerson, 6.00],
            'crm.support.lead@example.com' => [EmployeeRole::TeamLead,    7.00],
            'crm.support.agent@example.com' => [EmployeeRole::SalesPerson, 5.00],
            'crm.ops.agent@example.com' => [EmployeeRole::GeneralManager, 10.00],
        ];

        foreach ($roleMap as $email => [$role, $rate]) {
            Employee::query()
                ->where('email', $email)
                ->update(['role' => $role->value, 'commission_rate' => $rate]);
        }
    }

    // ── Phase 2 — Sales targets (monthly + quarterly) ─────────────────────────

    private function seedSalesTargets(): void
    {
        $month = now()->format('Y-m');
        $quarter = 'Q'.ceil((int) now()->format('n') / 3).'-'.now()->format('Y');

        $monthlyTargets = [
            ['crm.sales.agent@example.com',   150_000_00, 10],
            ['crm.support.agent@example.com',  80_000_00,  6],
            ['crm.sales.lead@example.com',    300_000_00, 20],
            ['crm.support.lead@example.com',  200_000_00, 15],
            ['crm.ops.agent@example.com',     500_000_00, 30],
        ];

        foreach ($monthlyTargets as [$email, $revenue, $leads]) {
            $employee = Employee::query()->where('email', $email)->first();
            if (! $employee) {
                continue;
            }

            SalesTarget::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'period' => $month],
                [
                    'period_type' => SalesTargetPeriodType::Monthly->value,
                    'target_revenue' => $revenue,
                    'target_leads' => $leads,
                ],
            );
        }

        $quarterlyTargets = [
            ['crm.sales.lead@example.com',   900_000_00, 60],
            ['crm.support.lead@example.com', 600_000_00, 45],
            ['crm.ops.agent@example.com',  1_500_000_00, 90],
        ];

        foreach ($quarterlyTargets as [$email, $revenue, $leads]) {
            $employee = Employee::query()->where('email', $email)->first();
            if (! $employee) {
                continue;
            }

            SalesTarget::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'period' => $quarter],
                [
                    'period_type' => SalesTargetPeriodType::Quarterly->value,
                    'target_revenue' => $revenue,
                    'target_leads' => $leads,
                ],
            );
        }
    }

    // ── Phase 3 — Commission ledgers ──────────────────────────────────────────

    private function seedCommissionLedgers(): void
    {
        $period = now()->format('Y-m');

        $ledgers = [
            'crm.sales.agent@example.com' => [
                [
                    'gross_amount' => 10_000_00,
                    'commission_rate' => 6.00,
                    'commission_amount' => 600_00,
                    'status' => CommissionLedgerStatus::Approved,
                    'approved_at' => now()->subDays(5),
                    'paid_at' => null,
                ],
                [
                    'gross_amount' => 7_500_00,
                    'commission_rate' => 6.00,
                    'commission_amount' => 450_00,
                    'status' => CommissionLedgerStatus::Pending,
                    'approved_at' => null,
                    'paid_at' => null,
                ],
                [
                    'gross_amount' => 20_000_00,
                    'commission_rate' => 6.00,
                    'commission_amount' => 1_200_00,
                    'status' => CommissionLedgerStatus::Paid,
                    'approved_at' => now()->subDays(15),
                    'paid_at' => now()->subDays(3),
                ],
            ],
            'crm.sales.lead@example.com' => [
                [
                    'gross_amount' => 85_000_00,
                    'commission_rate' => 8.00,
                    'commission_amount' => 6_800_00,
                    'status' => CommissionLedgerStatus::Approved,
                    'approved_at' => now()->subDays(7),
                    'paid_at' => null,
                ],
                [
                    'gross_amount' => 35_000_00,
                    'commission_rate' => 8.00,
                    'commission_amount' => 2_800_00,
                    'status' => CommissionLedgerStatus::Paid,
                    'approved_at' => now()->subDays(20),
                    'paid_at' => now()->subDays(10),
                ],
            ],
        ];

        foreach ($ledgers as $email => $entries) {
            $employee = Employee::query()->where('email', $email)->first();
            if (! $employee) {
                continue;
            }

            foreach ($entries as $data) {
                CommissionLedger::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period' => $period,
                        'gross_amount' => $data['gross_amount'],
                    ],
                    array_merge($data, [
                        'employee_id' => $employee->id,
                        'period' => $period,
                        'order_attribution_id' => null,
                        'status' => $data['status']->value,
                    ]),
                );
            }
        }
    }

    // ── Phase 4 — CRM activities + tasks on service requests ─────────────────

    private function seedCrmActivitiesAndTasks(): void
    {
        $serviceRequest = ServiceRequest::query()->first();
        $employee = Employee::query()->where('email', 'crm.sales.agent@example.com')->first();

        if (! $serviceRequest || ! $employee) {
            return;
        }

        $activities = [
            [
                'type' => CrmActivityType::Call,
                'outcome' => CrmActivityOutcome::Reached,
                'subject' => 'Initial discovery call',
                'body' => 'Spoke with the client. Interested in corporate training for 20 staff.',
                'scheduled_at' => now()->subDays(7),
                'completed_at' => now()->subDays(7),
                'created_at' => now()->subDays(7),
            ],
            [
                'type' => CrmActivityType::WhatsApp,
                'outcome' => CrmActivityOutcome::Interested,
                'subject' => 'Sent brochure via WhatsApp',
                'body' => 'Shared the corporate training catalogue and pricing sheet.',
                'scheduled_at' => null,
                'completed_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
            ],
            [
                'type' => CrmActivityType::Email,
                'outcome' => CrmActivityOutcome::NoAnswer,
                'subject' => 'Follow-up email after WhatsApp',
                'body' => 'Sent a follow-up email with the proposal PDF attached.',
                'scheduled_at' => null,
                'completed_at' => now()->subDays(3),
                'created_at' => now()->subDays(3),
            ],
            [
                'type' => CrmActivityType::Meeting,
                'outcome' => CrmActivityOutcome::Callback,
                'subject' => 'Online product demo',
                'body' => 'Demoed the LMS platform. Client requested follow-up with their IT team.',
                'scheduled_at' => now()->addDays(3),
                'completed_at' => null,
                'created_at' => now()->subDays(2),
            ],
        ];

        foreach ($activities as $data) {
            CrmActivity::query()->firstOrCreate(
                [
                    'entity_type' => ServiceRequest::class,
                    'entity_id' => $serviceRequest->id,
                    'subject' => $data['subject'],
                ],
                array_merge($data, [
                    'entity_type' => ServiceRequest::class,
                    'entity_id' => $serviceRequest->id,
                    'employee_id' => $employee->id,
                    'type' => $data['type']->value,
                    'outcome' => $data['outcome']->value,
                ]),
            );
        }

        $tasks = [
            [
                'title' => 'Send formal proposal document',
                'priority' => CrmTaskPriority::High,
                'status' => CrmTaskStatus::Todo,
                'due_at' => now()->addDays(2),
                'is_done' => false,
                'notes' => 'Include pricing table and training schedule.',
                'completed_at' => null,
            ],
            [
                'title' => 'Follow up after demo',
                'priority' => CrmTaskPriority::Normal,
                'status' => CrmTaskStatus::InProgress,
                'due_at' => now()->subDay(),
                'is_done' => false,
                'notes' => null,
                'completed_at' => null,
            ],
            [
                'title' => 'Book kick-off meeting',
                'priority' => CrmTaskPriority::Urgent,
                'status' => CrmTaskStatus::Done,
                'due_at' => now()->addDays(7),
                'is_done' => true,
                'notes' => 'Scheduled for next Tuesday.',
                'completed_at' => now()->subHours(3),
            ],
        ];

        foreach ($tasks as $data) {
            CrmTask::query()->firstOrCreate(
                [
                    'entity_type' => ServiceRequest::class,
                    'entity_id' => $serviceRequest->id,
                    'title' => $data['title'],
                ],
                array_merge($data, [
                    'entity_type' => ServiceRequest::class,
                    'entity_id' => $serviceRequest->id,
                    'employee_id' => $employee->id,
                    'priority' => $data['priority']->value,
                    'status' => $data['status']->value,
                ]),
            );
        }
    }

    // ── Phase 5 — Companies, deals, quotes ───────────────────────────────────

    private function seedCompaniesDealsQuotes(): void
    {
        $employee = Employee::query()->where('email', 'crm.sales.agent@example.com')->first();
        $leadEmployee = Employee::query()->where('email', 'crm.sales.lead@example.com')->first();
        $serviceRequest = ServiceRequest::query()->first();

        $companies = [
            [
                'name' => 'Al-Rashid Group',
                'industry' => 'Financial Services',
                'size' => 'large',
                'website' => 'https://alrashid.example.com',
                'tax_id' => '310012345600003',
                'deals' => [
                    [
                        'title' => 'Leadership Development Programme — Q3',
                        'stage' => DealStage::Proposal,
                        'value' => 85_000_00,
                        'probability' => 60,
                        'expected_close_date' => now()->addDays(30)->toDateString(),
                        'employee' => $employee,
                        'quotes' => [
                            [
                                'status' => QuoteStatus::Sent,
                                'subtotal' => 85_000_00,
                                'discount' => 5_000_00,
                                'total' => 80_000_00,
                                'valid_until' => now()->addDays(14)->toDateString(),
                                'sent_at' => now()->subDays(3),
                            ],
                            [
                                'status' => QuoteStatus::Draft,
                                'subtotal' => 90_000_00,
                                'discount' => 8_000_00,
                                'total' => 82_000_00,
                                'valid_until' => now()->addDays(21)->toDateString(),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Maqam Tech Solutions',
                'industry' => 'Technology',
                'size' => 'medium',
                'website' => 'https://maqamtech.example.com',
                'tax_id' => '310087654300002',
                'deals' => [
                    [
                        'title' => 'Technical Team Upskilling — Python & Data',
                        'stage' => DealStage::Negotiation,
                        'value' => 120_000_00,
                        'probability' => 75,
                        'expected_close_date' => now()->addDays(14)->toDateString(),
                        'employee' => $employee,
                        'quotes' => [
                            [
                                'status' => QuoteStatus::Accepted,
                                'subtotal' => 120_000_00,
                                'discount' => 10_000_00,
                                'total' => 110_000_00,
                                'valid_until' => now()->addDays(7)->toDateString(),
                                'sent_at' => now()->subDays(5),
                                'accepted_at' => now()->subDays(2),
                            ],
                        ],
                    ],
                    [
                        'title' => 'Soft Skills Workshop Series',
                        'stage' => DealStage::Prospecting,
                        'value' => 40_000_00,
                        'probability' => 20,
                        'expected_close_date' => now()->addDays(60)->toDateString(),
                        'employee' => $employee,
                        'quotes' => [],
                    ],
                    [
                        'title' => 'Cybersecurity Awareness Programme',
                        'stage' => DealStage::ClosedLost,
                        'value' => 55_000_00,
                        'probability' => 0,
                        'expected_close_date' => now()->subDays(10)->toDateString(),
                        'lost_reason' => 'Client chose a competitor with lower pricing.',
                        'employee' => $employee,
                        'quotes' => [
                            [
                                'status' => QuoteStatus::Rejected,
                                'subtotal' => 55_000_00,
                                'discount' => 0,
                                'total' => 55_000_00,
                                'valid_until' => now()->subDays(15)->toDateString(),
                                'sent_at' => now()->subDays(20),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Noor Retail Co.',
                'industry' => 'Retail',
                'size' => 'small',
                'website' => null,
                'tax_id' => '310099111200001',
                'deals' => [
                    [
                        'title' => 'Customer Service Excellence Training',
                        'stage' => DealStage::ClosedWon,
                        'value' => 35_000_00,
                        'probability' => 100,
                        'expected_close_date' => now()->subDays(5)->toDateString(),
                        'employee' => $leadEmployee,
                        'quotes' => [
                            [
                                'status' => QuoteStatus::Accepted,
                                'subtotal' => 35_000_00,
                                'discount' => 0,
                                'total' => 35_000_00,
                                'valid_until' => now()->subDays(10)->toDateString(),
                                'sent_at' => now()->subDays(20),
                                'accepted_at' => now()->subDays(6),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Baraka Healthcare',
                'industry' => 'Healthcare',
                'size' => 'medium',
                'website' => 'https://baraka-hc.example.com',
                'tax_id' => '310076543200004',
                'deals' => [
                    [
                        'title' => 'Medical Staff Compliance Training',
                        'stage' => DealStage::Qualification,
                        'value' => 70_000_00,
                        'probability' => 40,
                        'expected_close_date' => now()->addDays(45)->toDateString(),
                        'employee' => $leadEmployee,
                        'quotes' => [],
                    ],
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            $dealsData = $companyData['deals'];
            unset($companyData['deals']);

            $company = Company::query()->updateOrCreate(
                ['name' => $companyData['name']],
                array_merge($companyData, ['account_manager_id' => $employee?->id]),
            );

            foreach ($dealsData as $dealData) {
                $quotesData = $dealData['quotes'];
                $dealEmployee = $dealData['employee'];
                unset($dealData['quotes'], $dealData['employee']);

                $deal = Deal::query()->updateOrCreate(
                    ['company_id' => $company->id, 'title' => $dealData['title']],
                    array_merge($dealData, [
                        'company_id' => $company->id,
                        'employee_id' => $dealEmployee?->id,
                        'service_request_id' => $serviceRequest?->id,
                        'stage' => $dealData['stage']->value,
                        'lost_reason' => $dealData['lost_reason'] ?? null,
                        'notes' => null,
                    ]),
                );

                foreach ($quotesData as $quoteData) {
                    Quote::query()->firstOrCreate(
                        ['deal_id' => $deal->id, 'total' => $quoteData['total']],
                        array_merge($quoteData, [
                            'deal_id' => $deal->id,
                            'status' => $quoteData['status']->value,
                            'sent_at' => $quoteData['sent_at'] ?? null,
                            'accepted_at' => $quoteData['accepted_at'] ?? null,
                        ]),
                    );
                }
            }
        }
    }

    // ── Phase 6 — CRM activities + tasks on deals ─────────────────────────────

    private function seedDealActivitiesAndTasks(): void
    {
        $salesAgent = Employee::query()->where('email', 'crm.sales.agent@example.com')->first();
        $salesLead = Employee::query()->where('email', 'crm.sales.lead@example.com')->first();

        if (! $salesAgent) {
            return;
        }

        $negotiationDeal = Deal::query()
            ->where('stage', DealStage::Negotiation->value)
            ->first();

        if ($negotiationDeal) {
            $dealActivities = [
                [
                    'type' => CrmActivityType::Call,
                    'outcome' => CrmActivityOutcome::Reached,
                    'subject' => 'Contract terms discussion',
                    'body' => 'Reviewed contract clauses with the procurement manager. Price agreed on 110,000 SAR.',
                    'scheduled_at' => now()->subDays(4),
                    'completed_at' => now()->subDays(4),
                    'created_at' => now()->subDays(4),
                    'employee' => $salesAgent,
                ],
                [
                    'type' => CrmActivityType::Email,
                    'outcome' => CrmActivityOutcome::Interested,
                    'subject' => 'Final contract draft sent',
                    'body' => 'Emailed the signed draft contract and invoice for review.',
                    'scheduled_at' => null,
                    'completed_at' => now()->subDays(2),
                    'created_at' => now()->subDays(2),
                    'employee' => $salesLead ?? $salesAgent,
                ],
                [
                    'type' => CrmActivityType::Note,
                    'outcome' => CrmActivityOutcome::Callback,
                    'subject' => 'Internal note — awaiting legal sign-off',
                    'body' => 'Client legal team is reviewing. Expected sign-off by end of week.',
                    'scheduled_at' => null,
                    'completed_at' => now()->subDay(),
                    'created_at' => now()->subDay(),
                    'employee' => $salesLead ?? $salesAgent,
                ],
            ];

            foreach ($dealActivities as $data) {
                $emp = $data['employee'];
                unset($data['employee']);

                CrmActivity::query()->firstOrCreate(
                    [
                        'entity_type' => Deal::class,
                        'entity_id' => $negotiationDeal->id,
                        'subject' => $data['subject'],
                    ],
                    array_merge($data, [
                        'entity_type' => Deal::class,
                        'entity_id' => $negotiationDeal->id,
                        'employee_id' => $emp->id,
                        'type' => $data['type']->value,
                        'outcome' => $data['outcome']->value,
                    ]),
                );
            }

            CrmTask::query()->firstOrCreate(
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $negotiationDeal->id,
                    'title' => 'Get countersigned contract from client',
                ],
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $negotiationDeal->id,
                    'employee_id' => $salesLead?->id ?? $salesAgent->id,
                    'title' => 'Get countersigned contract from client',
                    'priority' => CrmTaskPriority::Urgent->value,
                    'status' => CrmTaskStatus::Todo->value,
                    'due_at' => now()->addDays(2),
                    'is_done' => false,
                    'notes' => 'Must be in before invoicing can proceed.',
                    'completed_at' => null,
                ],
            );
        }

        $proposalDeal = Deal::query()
            ->where('stage', DealStage::Proposal->value)
            ->first();

        if ($proposalDeal) {
            CrmActivity::query()->firstOrCreate(
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'subject' => 'Proposal walkthrough call',
                ],
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'employee_id' => $salesAgent->id,
                    'type' => CrmActivityType::Call->value,
                    'outcome' => CrmActivityOutcome::Interested->value,
                    'subject' => 'Proposal walkthrough call',
                    'body' => 'Walked the client through the proposal. They requested a 10% discount.',
                    'scheduled_at' => now()->subDays(2),
                    'completed_at' => now()->subDays(2),
                    'created_at' => now()->subDays(2),
                ],
            );

            CrmActivity::query()->firstOrCreate(
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'subject' => 'Site visit to client HQ',
                ],
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'employee_id' => $salesLead?->id ?? $salesAgent->id,
                    'type' => CrmActivityType::Visit->value,
                    'outcome' => CrmActivityOutcome::Callback->value,
                    'subject' => 'Site visit to client HQ',
                    'body' => 'Met HR and IT decision-makers on site. Positive response overall.',
                    'scheduled_at' => now()->addDays(5),
                    'completed_at' => null,
                    'created_at' => now()->subDay(),
                ],
            );

            CrmTask::query()->firstOrCreate(
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'title' => 'Revise proposal with 8% discount',
                ],
                [
                    'entity_type' => Deal::class,
                    'entity_id' => $proposalDeal->id,
                    'employee_id' => $salesAgent->id,
                    'title' => 'Revise proposal with 8% discount',
                    'priority' => CrmTaskPriority::High->value,
                    'status' => CrmTaskStatus::InProgress->value,
                    'due_at' => now()->addDay(),
                    'is_done' => false,
                    'notes' => null,
                    'completed_at' => null,
                ],
            );
        }
    }

    // ── Phase 7 — Assignment rules ────────────────────────────────────────────

    private function seedAssignmentRules(): void
    {
        $salesAgent = Employee::query()->where('email', 'crm.sales.agent@example.com')->first();
        $supportAgent = Employee::query()->where('email', 'crm.support.agent@example.com')->first();
        $salesLead = Employee::query()->where('email', 'crm.sales.lead@example.com')->first();

        AssignmentRule::query()->updateOrCreate(
            ['name' => 'Sales leads — auto assign'],
            [
                'conditions' => [
                    ['field' => 'owner_team', 'operator' => 'equals', 'value' => 'sales'],
                ],
                'employee_ids' => array_values(array_filter([$salesAgent?->id, $salesLead?->id])),
                'strategy' => AssignmentStrategy::LeastLoaded->value,
                'priority' => 1,
                'is_active' => true,
            ],
        );

        AssignmentRule::query()->updateOrCreate(
            ['name' => 'Support leads — round robin'],
            [
                'conditions' => [
                    ['field' => 'owner_team', 'operator' => 'equals', 'value' => 'support'],
                ],
                'employee_ids' => array_values(array_filter([$supportAgent?->id])),
                'strategy' => AssignmentStrategy::RoundRobin->value,
                'priority' => 2,
                'is_active' => true,
            ],
        );

        AssignmentRule::query()->updateOrCreate(
            ['name' => 'All unassigned leads — fallback'],
            [
                'conditions' => [],
                'employee_ids' => array_values(array_filter([$salesAgent?->id, $supportAgent?->id])),
                'strategy' => AssignmentStrategy::LeastLoaded->value,
                'priority' => 99,
                'is_active' => true,
            ],
        );
    }

    // ── Phase 8 — Workflow rules ──────────────────────────────────────────────

    private function seedWorkflowRules(): void
    {
        $salesAgent = Employee::query()->where('email', 'crm.sales.agent@example.com')->first();
        $salesLead = Employee::query()->where('email', 'crm.sales.lead@example.com')->first();

        // LeadCreated → create initial call task
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'New lead → create follow-up task'],
            [
                'trigger_event' => WorkflowTrigger::LeadCreated->value,
                'conditions' => [],
                'actions' => [
                    [
                        'type' => WorkflowActionType::CreateTask->value,
                        'title' => 'Initial contact call',
                        'priority' => CrmTaskPriority::High->value,
                        'due_in_hours' => 4,
                        'employee_id' => $salesAgent?->id,
                    ],
                ],
                'is_active' => true,
            ],
        );

        // LeadAssigned → notify assigned employee with a task
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Lead assigned → create onboarding task'],
            [
                'trigger_event' => WorkflowTrigger::LeadAssigned->value,
                'conditions' => [],
                'actions' => [
                    [
                        'type' => WorkflowActionType::CreateTask->value,
                        'title' => 'Review lead profile and history',
                        'priority' => CrmTaskPriority::Normal->value,
                        'due_in_hours' => 24,
                        'employee_id' => null,
                    ],
                ],
                'is_active' => true,
            ],
        );

        // LeadStatusChanged to in_progress → send proposal task
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Lead in_progress → schedule follow-up'],
            [
                'trigger_event' => WorkflowTrigger::LeadStatusChanged->value,
                'conditions' => [
                    ['field' => 'new_status', 'operator' => 'equals', 'value' => 'follow_up_dated'],
                ],
                'actions' => [
                    [
                        'type' => WorkflowActionType::CreateTask->value,
                        'title' => 'Send proposal document',
                        'priority' => CrmTaskPriority::Normal->value,
                        'due_in_hours' => 48,
                        'employee_id' => null,
                    ],
                ],
                'is_active' => true,
            ],
        );

        // LeadStatusChanged to resolved → assign to sales lead
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Lead resolved → escalate to sales lead'],
            [
                'trigger_event' => WorkflowTrigger::LeadStatusChanged->value,
                'conditions' => [
                    ['field' => 'new_status', 'operator' => 'equals', 'value' => 'resolved'],
                ],
                'actions' => [
                    [
                        'type' => WorkflowActionType::AssignEmployee->value,
                        'employee_id' => $salesLead?->id,
                        'field' => 'assigned_to',
                    ],
                ],
                'is_active' => true,
            ],
        );

        // DealCreated → create discovery task
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Deal created → discovery call task'],
            [
                'trigger_event' => WorkflowTrigger::DealCreated->value,
                'conditions' => [],
                'actions' => [
                    [
                        'type' => WorkflowActionType::CreateTask->value,
                        'title' => 'Schedule discovery call',
                        'priority' => CrmTaskPriority::High->value,
                        'due_in_hours' => 8,
                        'employee_id' => $salesAgent?->id,
                    ],
                ],
                'is_active' => true,
            ],
        );

        // DealStageChanged to Negotiation → urgent closing task
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Deal negotiation → create closing task'],
            [
                'trigger_event' => WorkflowTrigger::DealStageChanged->value,
                'conditions' => [
                    ['field' => 'new_stage', 'operator' => 'equals', 'value' => DealStage::Negotiation->value],
                ],
                'actions' => [
                    [
                        'type' => WorkflowActionType::CreateTask->value,
                        'title' => 'Prepare final contract terms',
                        'priority' => CrmTaskPriority::Urgent->value,
                        'due_in_hours' => 24,
                        'employee_id' => $salesAgent?->id,
                    ],
                ],
                'is_active' => true,
            ],
        );

        // DealStageChanged to ClosedWon → update lead status to closed
        WorkflowRule::query()->updateOrCreate(
            ['name' => 'Deal closed won → mark lead closed'],
            [
                'trigger_event' => WorkflowTrigger::DealStageChanged->value,
                'conditions' => [
                    ['field' => 'new_stage', 'operator' => 'equals', 'value' => DealStage::ClosedWon->value],
                ],
                'actions' => [
                    [
                        'type' => WorkflowActionType::UpdateStatus->value,
                        'new_status' => 'closed',
                    ],
                ],
                'is_active' => true,
            ],
        );
    }
}
