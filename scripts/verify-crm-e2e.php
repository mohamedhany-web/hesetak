<?php

/**
 * CRM حصتك end-to-end verification.
 * Usage: php scripts/verify-crm-e2e.php
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdvancedCourse;
use App\Models\CrmAuditLog;
use App\Models\CrmCommission;
use App\Models\CrmGroup;
use App\Models\CrmGroupMember;
use App\Models\Order;
use App\Models\SalesLead;
use App\Models\StudentCourseEnrollment;
use App\Models\User;
use App\Services\Crm\CrmAccessService;
use App\Services\Crm\CrmCommissionService;
use App\Services\Crm\CrmLeadService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$errors = [];
$checks = [];

function check(string $name, bool $ok, array &$checks, array &$errors, ?string $detail = null): void
{
    $checks[$name] = $ok;
    if (! $ok) {
        $errors[] = $name.($detail ? ': '.$detail : '');
    }
}

// Schema
check('crm_groups_table', Schema::hasTable('crm_groups'), $checks, $errors);
check('crm_commissions_table', Schema::hasTable('crm_commissions'), $checks, $errors);
check('crm_audit_logs_table', Schema::hasTable('crm_audit_logs'), $checks, $errors);
check('sales_leads_marketing_owner', Schema::hasColumn('sales_leads', 'marketing_owner_id'), $checks, $errors);
check('orders_sales_lead_id', Schema::hasColumn('orders', 'sales_lead_id'), $checks, $errors);

// Status count
check('status_labels_13', count(SalesLead::statusLabels()) === 13, $checks, $errors, 'got '.count(SalesLead::statusLabels()));

// Routes
$routeNames = [
    'admin.crm.dashboard',
    'admin.crm.leads.index',
    'admin.crm.commissions.index',
    'admin.crm.audit.index',
    'admin.crm.groups.index',
    'employee.crm.dashboard',
    'employee.crm.leads.index',
    'employee.crm.leads.create',
];
foreach ($routeNames as $name) {
    check('route_'.$name, (bool) Route::has($name), $checks, $errors);
}

// Users
$marketingJob = \App\Models\EmployeeJob::where('code', 'crm_marketing')->first();
$salesJob = \App\Models\EmployeeJob::where('code', 'sales')->first();
check('marketing_job_exists', (bool) $marketingJob, $checks, $errors);
check('sales_job_exists', (bool) $salesJob, $checks, $errors);

$marketingUser = User::employees()->where('employee_job_id', $marketingJob?->id)->first()
    ?? User::employees()->first();
$salesUser = User::employees()->where('employee_job_id', $salesJob?->id)->first()
    ?? User::whereIn('role', ['admin', 'super_admin'])->first();
$adminUser = User::where('role', 'super_admin')->first()
    ?? User::where('role', 'admin')->first();
$student = User::where('role', 'student')->first();

check('has_marketing_or_employee', (bool) $marketingUser, $checks, $errors);
check('has_sales_or_admin', (bool) $salesUser, $checks, $errors);
check('has_admin', (bool) $adminUser, $checks, $errors);
check('has_student', (bool) $student, $checks, $errors);

$course = AdvancedCourse::where('is_active', true)->first();

$lead = null;
$group = null;
$order = null;

if ($marketingUser && $adminUser && $salesUser && $student && $course) {
    try {
        // Ensure marketing user has correct job for role check
        if ($marketingJob && (int) $marketingUser->employee_job_id !== (int) $marketingJob->id) {
            $marketingUser->update(['employee_job_id' => $marketingJob->id]);
            $marketingUser->refresh();
        }

        $creator = CrmAccessService::crmRole($marketingUser) === 'marketing' ? $marketingUser : $adminUser;
        // Create group
        $group = CrmGroup::create([
            'name' => '[TEST CRM] Group '.uniqid(),
            'team_leader_id' => $adminUser->id,
            'is_active' => true,
        ]);
        CrmGroupMember::create([
            'crm_group_id' => $group->id,
            'user_id' => $marketingUser->id,
            'role' => CrmGroupMember::ROLE_MARKETING,
        ]);
        CrmGroupMember::create([
            'crm_group_id' => $group->id,
            'user_id' => $salesUser->id,
            'role' => CrmGroupMember::ROLE_SALES,
        ]);
        check('group_created', true, $checks, $errors);

        $lead = CrmLeadService::createLead([
            'name' => '[TEST CRM] Lead '.uniqid(),
            'email' => 'crm-test-'.uniqid().'@test.local',
            'phone' => '010'.random_int(10000000, 99999999),
            'source' => SalesLead::SOURCE_WEBSITE,
            'interested_advanced_course_id' => $course->id,
            'crm_group_id' => $group->id,
        ], $creator);

        check('lead_created', $lead->id > 0, $checks, $errors);
        check('lead_status_new', $lead->status === SalesLead::STATUS_NEW, $checks, $errors, $lead->status);
        check('marketing_owner_immutable', (int) $lead->marketing_owner_id === (int) $creator->id, $checks, $errors);

        // Marketing cannot edit after assign
        $lead->update(['assigned_to' => $salesUser->id, 'status' => SalesLead::STATUS_ASSIGNED, 'assigned_at' => now()]);
        check('marketing_cannot_edit_assigned', ! CrmAccessService::canEditLead($marketingUser, $lead->fresh()), $checks, $errors);

        // Admin assigns (already assigned manually for test)
        check('sales_can_view', CrmAccessService::canViewLead($salesUser, $lead->fresh()), $checks, $errors);

        // Sales transitions
        $steps = [
            SalesLead::STATUS_CONTACTED,
            SalesLead::STATUS_INTERESTED,
            SalesLead::STATUS_PLACEMENT_TEST,
            SalesLead::STATUS_OFFER_SENT,
            SalesLead::STATUS_PAYMENT_PENDING,
        ];
        foreach ($steps as $status) {
            CrmLeadService::transitionStatus($lead->fresh(), $status, $salesUser, 'test step');
            $lead = $lead->fresh();
            check('transition_'.$status, $lead->status === $status, $checks, $errors);
        }

        // Invalid transition blocked
        $blocked = false;
        try {
            CrmLeadService::transitionStatus($lead, SalesLead::STATUS_CLOSED_WON, $salesUser);
        } catch (\InvalidArgumentException $e) {
            $blocked = true;
        }
        check('invalid_transition_blocked', $blocked, $checks, $errors);

        // Create pending order linked to lead
        $order = Order::create([
            'user_id' => $student->id,
            'advanced_course_id' => $course->id,
            'amount' => 1000,
            'original_amount' => 1000,
            'payment_method' => 'cash',
            'status' => Order::STATUS_PENDING,
            'sales_lead_id' => $lead->id,
        ]);
        $lead->update(['order_id' => $order->id]);

        // Simulate approval path: set approved + enrollment then call CRM handler
        $enrollment = StudentCourseEnrollment::query()
            ->where('user_id', $student->id)
            ->where('advanced_course_id', $course->id)
            ->first();

        if (! $enrollment) {
            $enrollment = StudentCourseEnrollment::create([
                'user_id' => $student->id,
                'advanced_course_id' => $course->id,
                'status' => 'active',
                'enrolled_at' => now(),
                'activated_at' => now(),
            ]);
        }

        $order->update(['status' => Order::STATUS_APPROVED, 'approved_at' => now(), 'approved_by' => $adminUser->id]);
        CrmCommissionService::handleOrderApproved($order->fresh(), $adminUser);

        $lead = $lead->fresh();
        check('lead_payment_confirmed_or_beyond', in_array($lead->status, [
            SalesLead::STATUS_PAYMENT_CONFIRMED,
            SalesLead::STATUS_ENROLLED,
            SalesLead::STATUS_COURSE_ACTIVE,
        ], true), $checks, $errors, $lead->status);

        $commissionCount = CrmCommission::where('sales_lead_id', $lead->id)->count();
        check('commissions_created', $commissionCount >= 1, $checks, $errors, 'count='.$commissionCount);

        $auditCount = CrmAuditLog::where('sales_lead_id', $lead->id)->count();
        check('audit_logs_created', $auditCount >= 3, $checks, $errors, 'count='.$auditCount);

        // Marketing owner immutable after updates
        $lead->marketing_owner_id = 99999;
        $lead->save();
        check('marketing_owner_stays', (int) $lead->fresh()->marketing_owner_id === (int) $creator->id, $checks, $errors);

        // Audit log immutable
        $auditDeleteBlocked = true;
        try {
            CrmAuditLog::first()?->delete();
            $auditDeleteBlocked = false;
        } catch (\Throwable $e) {
            $auditDeleteBlocked = true;
        }
        // boot returns false on delete - may not throw
        $before = CrmAuditLog::count();
        CrmAuditLog::first()?->delete();
        check('audit_immutable', CrmAuditLog::count() === $before, $checks, $errors);

    } catch (\Throwable $e) {
        $errors[] = 'exception: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine();
    } finally {
        // Cleanup
        if ($lead) {
            CrmCommission::where('sales_lead_id', $lead->id)->delete();
            CrmAuditLog::where('sales_lead_id', $lead->id)->delete();
            $lead->forceDelete();
        }
        if ($order) {
            $order->delete();
        }
        if ($group) {
            CrmGroupMember::where('crm_group_id', $group->id)->delete();
            $group->delete();
        }
    }
}

$passed = count(array_filter($checks));
$total = count($checks);
$ok = empty($errors);

$result = [
    'ok' => $ok,
    'passed' => $passed,
    'total' => $total,
    'failed' => $errors,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($ok ? 0 : 1);
