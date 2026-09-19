<?php

/**
 * CRM حصتك — اختبار شامل لكل الأدوار والوظائف.
 * ينشئ حسابات تجريبية ويختبر مسار العمل كاملاً + صفحات HTTP.
 *
 * Usage: php scripts/verify-crm-roles-full.php
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\AdvancedCourse;
use App\Models\CrmAuditLog;
use App\Models\CrmCommission;
use App\Models\CrmGroup;
use App\Models\CrmGroupMember;
use App\Models\EmployeeJob;
use App\Models\Order;
use App\Models\SalesLead;
use App\Models\StudentCourseEnrollment;
use App\Models\User;
use App\Services\Crm\CrmAccessService;
use App\Services\Crm\CrmCommissionService;
use App\Services\Crm\CrmLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$errors = [];
$checks = [];
$accounts = [];

function check(string $name, bool $ok, array &$checks, array &$errors, ?string $detail = null): void
{
    $checks[$name] = $ok;
    if (! $ok) {
        $errors[] = $name.($detail ? ': '.$detail : '');
    }
}

function ensureEmployee(string $email, string $name, string $jobCode, string $phone): User
{
    $job = EmployeeJob::where('code', $jobCode)->firstOrFail();

    $user = User::where('email', $email)->first();
    $data = [
        'name' => $name,
        'phone' => $phone,
        'password' => Hash::make('CrmTest@2026'),
        'employee_job_id' => $job->id,
        'role' => 'student',
        'is_employee' => true,
        'is_active' => true,
        'hire_date' => now()->toDateString(),
        'employee_code' => 'CRM-'.strtoupper(str_replace('_', '', $jobCode)).'-T',
    ];

    if ($user) {
        $user->update($data);
        $user->refresh();
    } else {
        $data['email'] = $email;
        $user = User::create($data);
    }

    return $user;
}

function httpStatus($kernel, string $method, string $uri, User $user, array $data = []): int
{
    Auth::login($user);
    $request = Request::create($uri, $method, $data);
    $request->setUserResolver(fn () => $user);

    try {
        $response = $kernel->handle($request);
        $code = $response->getStatusCode();
        $kernel->terminate($request, $response);

        return $code;
    } catch (\Throwable $e) {
        return 500;
    } finally {
        Auth::logout();
    }
}

// ── 1. وظائف CRM ─────────────────────────────────────────────
$jobCodes = ['crm_marketing', 'sales', 'crm_team_leader', 'crm_finance'];
foreach ($jobCodes as $code) {
    check('job_'.$code, (bool) EmployeeJob::where('code', $code)->first(), $checks, $errors);
}

// ── 2. إنشاء حسابات تجريبية ───────────────────────────────────
$marketing = ensureEmployee('crm-test-marketing@glottical.test', 'CRM تسويق تجريبي', 'crm_marketing', '01099001001');
$sales = ensureEmployee('crm-test-sales@glottical.test', 'CRM سيلز تجريبي', 'sales', '01099001002');
$teamLeader = ensureEmployee('crm-test-tl@glottical.test', 'CRM قائد فريق تجريبي', 'crm_team_leader', '01099001003');
$finance = ensureEmployee('crm-test-finance@glottical.test', 'CRM مالية تجريبي', 'crm_finance', '01099001004');
$admin = User::where('role', 'super_admin')->first() ?? User::where('role', 'admin')->first();
$student = User::where('role', 'student')->where('is_employee', false)->first();
$course = AdvancedCourse::where('is_active', true)->first();

check('account_marketing', CrmAccessService::crmRole($marketing) === 'marketing', $checks, $errors);
check('account_sales', CrmAccessService::crmRole($sales) === 'sales', $checks, $errors);
check('account_team_leader', CrmAccessService::crmRole($teamLeader) === 'team_leader', $checks, $errors);
check('account_finance', CrmAccessService::crmRole($finance) === 'finance', $checks, $errors);
check('account_admin', (bool) $admin, $checks, $errors);
check('has_student', (bool) $student, $checks, $errors);
check('has_course', (bool) $course, $checks, $errors);

$accounts = [
    'marketing' => ['email' => $marketing->email, 'password' => 'CrmTest@2026'],
    'sales' => ['email' => $sales->email, 'password' => 'CrmTest@2026'],
    'team_leader' => ['email' => $teamLeader->email, 'password' => 'CrmTest@2026'],
    'finance' => ['email' => $finance->email, 'password' => 'CrmTest@2026'],
    'admin' => ['email' => $admin?->email, 'password' => '(كلمة مرور المدير الحالية)'],
];

$lead = null;
$group = null;
$order = null;
$testSuffix = uniqid();

if ($admin && $student && $course) {
    try {
        // ── 3. مجموعة CRM + أعضاء ────────────────────────────────
        $group = CrmGroup::create([
            'name' => '[TEST CRM FULL] '.$testSuffix,
            'team_leader_id' => $teamLeader->id,
            'is_active' => true,
        ]);
        CrmGroupMember::create([
            'crm_group_id' => $group->id,
            'user_id' => $marketing->id,
            'role' => CrmGroupMember::ROLE_MARKETING,
        ]);
        CrmGroupMember::create([
            'crm_group_id' => $group->id,
            'user_id' => $sales->id,
            'role' => CrmGroupMember::ROLE_SALES,
        ]);
        check('group_created_with_members', $group->members()->count() === 2, $checks, $errors);

        // ── 4. تسويق: إنشاء Lead ───────────────────────────────
        $lead = CrmLeadService::createLead([
            'name' => '[TEST FULL] Lead '.$testSuffix,
            'email' => 'lead-'.$testSuffix.'@test.local',
            'phone' => '010'.random_int(10000000, 99999999),
            'source' => SalesLead::SOURCE_WEBSITE,
            'interested_advanced_course_id' => $course->id,
            'crm_group_id' => $group->id,
        ], $marketing);

        check('marketing_create_lead', $lead->status === SalesLead::STATUS_NEW, $checks, $errors);
        check('marketing_owner_set', (int) $lead->marketing_owner_id === (int) $marketing->id, $checks, $errors);
        check('marketing_can_view_own', CrmAccessService::canViewLead($marketing, $lead), $checks, $errors);
        check('sales_cannot_view_unassigned', ! CrmAccessService::canViewLead($sales, $lead), $checks, $errors);

        // ── 5. إدارة: تعيين لسيلز ──────────────────────────────
        CrmLeadService::assignToSales($lead->fresh(), $sales, $admin, $group->id);
        $lead = $lead->fresh();
        check('admin_assign_lead', $lead->status === SalesLead::STATUS_ASSIGNED, $checks, $errors);
        check('assigned_to_sales', (int) $lead->assigned_to === (int) $sales->id, $checks, $errors);
        check('marketing_cannot_edit_after_assign', ! CrmAccessService::canEditLead($marketing, $lead), $checks, $errors);
        check('sales_can_view_assigned', CrmAccessService::canViewLead($sales, $lead), $checks, $errors);
        check('sales_can_edit_assigned', CrmAccessService::canEditLead($sales, $lead), $checks, $errors);
        check('team_leader_can_view_team_lead', CrmAccessService::canViewLead($teamLeader, $lead), $checks, $errors);
        check('tl_can_manage_team', CrmAccessService::canManageTeam($teamLeader, $group), $checks, $errors);
        check('tl_can_assign_permission', CrmAccessService::canAssignLead($teamLeader, $lead->fresh()), $checks, $errors);
        check('tl_can_add_notes', CrmAccessService::canAddNotes($teamLeader, $lead->fresh()), $checks, $errors);
        check('finance_view_all_orders', CrmAccessService::canViewAllOrders($finance), $checks, $errors);

        $leadForTlAssign = CrmLeadService::createLead([
            'name' => '[TEST TL ASSIGN] '.$testSuffix,
            'email' => 'tl-assign-'.$testSuffix.'@test.local',
            'phone' => '010'.random_int(10000000, 99999999),
            'source' => SalesLead::SOURCE_WEBSITE,
            'crm_group_id' => $group->id,
        ], $marketing);
        CrmLeadService::assignToSales($leadForTlAssign->fresh(), $sales, $teamLeader, $group->id);
        check('tl_assign_lead_to_sales', (int) $leadForTlAssign->fresh()->assigned_to === (int) $sales->id, $checks, $errors);

        // ── 6. سيلز: انتقالات + ملاحظة ─────────────────────────
        $salesSteps = [
            SalesLead::STATUS_CONTACTED,
            SalesLead::STATUS_INTERESTED,
            SalesLead::STATUS_PLACEMENT_TEST,
            SalesLead::STATUS_OFFER_SENT,
            SalesLead::STATUS_PAYMENT_PENDING,
        ];
        foreach ($salesSteps as $status) {
            CrmLeadService::transitionStatus($lead->fresh(), $status, $sales, 'خطوة اختبار');
            $lead = $lead->fresh();
            check('sales_transition_'.$status, $lead->status === $status, $checks, $errors);
        }

        CrmLeadService::addNote($lead, 'ملاحظة اختبار من السيلز', $sales);
        check('sales_add_note', str_contains($lead->fresh()->notes ?? '', 'ملاحظة اختبار'), $checks, $errors);

        $skipBlocked = false;
        try {
            CrmLeadService::transitionStatus($lead, SalesLead::STATUS_CLOSED_WON, $sales);
        } catch (\InvalidArgumentException) {
            $skipBlocked = true;
        }
        check('sales_cannot_skip_to_won', $skipBlocked, $checks, $errors);

        // تسويق لا يستطيع تغيير الحالة
        $marketingBlocked = false;
        try {
            CrmLeadService::transitionStatus($lead->fresh(), SalesLead::STATUS_CONTACTED, $marketing);
        } catch (\InvalidArgumentException) {
            $marketingBlocked = true;
        }
        check('marketing_cannot_transition', $marketingBlocked, $checks, $errors);

        // ── 7. مالية: رؤية Lead + تأكيد دفع ────────────────────
        check('finance_can_view_payment_pending', CrmAccessService::canViewLead($finance, $lead->fresh()), $checks, $errors);
        check('finance_view_all_leads', CrmAccessService::hasCrmPermission($finance, 'crm_view_all_leads'), $checks, $errors);
        check('finance_sales_financial_reports', CrmAccessService::canViewSalesFinancialReports($finance), $checks, $errors);
        check('finance_can_view_all_leads_query', CrmAccessService::leadsQueryFor($finance)->count() >= SalesLead::count() - 1, $checks, $errors);
        check('finance_can_confirm_payment', CrmAccessService::canTransitionStatus($finance, $lead->fresh(), SalesLead::STATUS_PAYMENT_CONFIRMED), $checks, $errors);

        // طلب + اعتماد (مسار الإدارة)
        $order = Order::create([
            'user_id' => $student->id,
            'advanced_course_id' => $course->id,
            'amount' => 2000,
            'original_amount' => 2000,
            'payment_method' => 'cash',
            'status' => Order::STATUS_PENDING,
            'sales_lead_id' => $lead->id,
        ]);
        $lead->update(['order_id' => $order->id]);

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

        $order->update(['status' => Order::STATUS_APPROVED, 'approved_at' => now(), 'approved_by' => $admin->id]);
        CrmCommissionService::handleOrderApproved($order->fresh(), $admin);
        $lead = $lead->fresh();

        check('order_approval_updates_lead', in_array($lead->status, [
            SalesLead::STATUS_PAYMENT_CONFIRMED,
            SalesLead::STATUS_ENROLLED,
            SalesLead::STATUS_COURSE_ACTIVE,
        ], true), $checks, $errors, $lead->status);

        $commissions = CrmCommission::where('sales_lead_id', $lead->id)->get();
        check('commissions_generated', $commissions->count() >= 2, $checks, $errors, 'count='.$commissions->count());

        $marketingCommission = $commissions->firstWhere('user_id', $marketing->id);
        $salesCommission = $commissions->firstWhere('user_id', $sales->id);
        check('marketing_commission', (bool) $marketingCommission, $checks, $errors);
        check('sales_commission', (bool) $salesCommission, $checks, $errors);

        // ── 8. اعتماد عمولات (مالية + إدارة) ───────────────────
        if ($marketingCommission) {
            CrmCommissionService::approveCommission($marketingCommission->fresh(), $finance);
            check('finance_approve_commission', $marketingCommission->fresh()->status === CrmCommission::STATUS_APPROVED, $checks, $errors);
        }
        if ($salesCommission) {
            CrmCommissionService::approveCommission($salesCommission->fresh(), $admin);
            check('admin_approve_commission', $salesCommission->fresh()->status === CrmCommission::STATUS_APPROVED, $checks, $errors);
        }

        $auditCount = CrmAuditLog::where('sales_lead_id', $lead->id)->count();
        check('audit_logs_recorded', $auditCount >= 5, $checks, $errors, 'count='.$auditCount);

        // مالك التسويق ثابت
        $lead->marketing_owner_id = 99999;
        $lead->save();
        check('marketing_owner_immutable', (int) $lead->fresh()->marketing_owner_id === (int) $marketing->id, $checks, $errors);

        // ── 9. HTTP — صفحات الإدارة ───────────────────────────
        $adminPages = [
            '/admin/crm',
            '/admin/crm/leads',
            '/admin/crm/leads/'.$lead->id,
            '/admin/crm/commissions',
            '/admin/crm/audit',
            '/admin/crm/groups',
            '/admin/crm/groups/create',
            '/admin/crm/groups/'.$group->id.'/edit',
            '/admin/crm/reports',
            '/admin/employee-jobs',
        ];
        foreach ($adminPages as $uri) {
            $code = httpStatus($kernel, 'GET', $uri, $admin);
            check('http_admin_'.str_replace(['/', '{', '}'], ['_', '', ''], $uri), $code < 400, $checks, $errors, "HTTP $code");
        }

        // ── 10. HTTP — صفحات الموظفين ──────────────────────────
        $employeePages = [
            'marketing' => [
                '/employee/crm',
                '/employee/crm/leads',
                '/employee/crm/leads/create',
                '/employee/crm/leads/'.$lead->id,
                '/employee/crm/commissions',
                '/employee/crm/reports',
                '/employee/crm/reports/create?type=weekly',
                '/employee/crm/messages',
            ],
            'sales' => [
                '/employee/crm',
                '/employee/crm/leads',
                '/employee/crm/leads/'.$lead->id,
                '/employee/crm/commissions',
                '/employee/crm/reports',
                '/employee/crm/messages',
            ],
            'team_leader' => [
                '/employee/crm',
                '/employee/crm/team',
                '/employee/crm/leads',
                '/employee/crm/leads/'.$lead->id,
                '/employee/crm/commissions',
                '/employee/crm/reports',
                '/employee/crm/messages',
            ],
            'finance' => [
                '/employee/crm',
                '/employee/crm/sales-financial',
                '/employee/crm/team',
                '/employee/crm/leads',
                '/employee/crm/leads/'.$lead->id,
                '/employee/crm/commissions',
                '/employee/crm/orders',
                '/employee/crm/messages',
            ],
        ];

        $roleUsers = [
            'marketing' => $marketing,
            'sales' => $sales,
            'team_leader' => $teamLeader,
            'finance' => $finance,
        ];

        foreach ($employeePages as $role => $uris) {
            foreach ($uris as $uri) {
                $code = httpStatus($kernel, 'GET', $uri, $roleUsers[$role]);
                check('http_'.$role.'_'.md5($uri), $code < 400, $checks, $errors, "$uri => HTTP $code");
            }
        }

        // سيلز لا يصل لصفحة إنشاء Lead
        $salesCreateCode = httpStatus($kernel, 'GET', '/employee/crm/leads/create', $sales);
        check('sales_blocked_from_create', $salesCreateCode === 403, $checks, $errors, "HTTP $salesCreateCode");

        // إعادة توجيه المبيعات القديمة
        $legacyRedirect = httpStatus($kernel, 'GET', '/employee/sales/leads', $sales);
        check('legacy_sales_leads_redirect', in_array($legacyRedirect, [200, 301, 302, 303, 307, 308], true), $checks, $errors, "HTTP $legacyRedirect");

    } catch (\Throwable $e) {
        $errors[] = 'exception: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine();
    } finally {
        if ($lead) {
            CrmCommission::where('sales_lead_id', $lead->id)->delete();
            CrmAuditLog::where('sales_lead_id', $lead->id)->delete();
            $lead->forceDelete();
        }
        if (isset($leadForTlAssign) && $leadForTlAssign) {
            CrmAuditLog::where('sales_lead_id', $leadForTlAssign->id)->delete();
            $leadForTlAssign->forceDelete();
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
    'test_accounts' => $accounts,
    'note' => 'الحسابات التجريبية تبقى في قاعدة البيانات للتجربة اليدوية. كلمة المرور: CrmTest@2026',
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($ok ? 0 : 1);
