<?php

/**
 * يفحص كل صفحات CRM — يتأكد من HTTP سليم وعدم ظهور أخطاء في HTML.
 * ينشئ Lead/مجموعة تجريبية لاختبار صفحات التفاصيل.
 *
 * Usage: php scripts/verify-crm-pages-no-errors.php
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
use App\Models\SalesLead;
use App\Models\User;
use App\Services\Crm\CrmLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$errorPatterns = [
    '/\b(ErrorException|Fatal error|Parse error|Uncaught)\b/i',
    '/\b(Undefined variable|Undefined array key|Attempt to read property)\b/i',
    '/\b(Call to a member function on null|Class .* not found)\b/i',
    '/\b(SQLSTATE\[|QueryException)\b/i',
    '/\b(Whoops\\\\|Ignition|stack trace|vendor\\\\laravel\\\\framework)\b/i',
    '/\b(View \[.*\] not found)\b/i',
    '/\b(Route \[.*\] not defined)\b/i',
    '/<title>Server Error<\/title>/i',
];

function ensureTestEmployee(string $email, string $jobCode, string $phone): ?User
{
    $job = EmployeeJob::where('code', $jobCode)->first();
    if (! $job) {
        return null;
    }

    return User::updateOrCreate(
        ['email' => $email],
        [
            'name' => 'CRM Page Test '.$jobCode,
            'phone' => $phone,
            'password' => Hash::make('CrmTest@2026'),
            'employee_job_id' => $job->id,
            'role' => 'student',
            'is_employee' => true,
            'is_active' => true,
            'hire_date' => now()->toDateString(),
            'employee_code' => 'PG-'.strtoupper(str_replace('_', '', $jobCode)),
        ]
    );
}

function fetchPage($kernel, string $uri, User $user): array
{
    Auth::login($user);
    $request = Request::create($uri, 'GET');
    $request->setUserResolver(fn () => $user);

    try {
        $response = $kernel->handle($request);
        $body = $response->getContent() ?: '';
        $code = $response->getStatusCode();
        $kernel->terminate($request, $response);
    } catch (\Throwable $e) {
        return ['code' => 500, 'body' => $e->getMessage(), 'exception' => $e->getMessage()];
    } finally {
        Auth::logout();
    }

    return ['code' => $code, 'body' => $body, 'exception' => null];
}

function bodyHasError(string $body, array $patterns): ?string
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $body, $m)) {
            return trim($m[0]);
        }
    }

    return null;
}

// ── حسابات + بيانات تجريبية لصفحات التفاصيل ─────────────────
$admin = User::where('role', 'super_admin')->first() ?? User::where('role', 'admin')->first();
$marketing = ensureTestEmployee('crm-page-mkt@hesetak.test', 'crm_marketing', '01099002001');
$sales = ensureTestEmployee('crm-page-sales@hesetak.test', 'sales', '01099002002');
$teamLeader = ensureTestEmployee('crm-page-tl@hesetak.test', 'crm_team_leader', '01099002003');
$finance = ensureTestEmployee('crm-page-fin@hesetak.test', 'crm_finance', '01099002004');
$course = AdvancedCourse::where('is_active', true)->first();

$demoLead = null;
$demoGroup = null;
$createdDemo = false;

if ($admin && $marketing && $sales && $teamLeader && $course) {
    $demoGroup = CrmGroup::firstOrCreate(
        ['name' => '[PAGE TEST] CRM Demo Group'],
        ['team_leader_id' => $teamLeader->id, 'is_active' => true]
    );
    $demoGroup->update(['team_leader_id' => $teamLeader->id, 'is_active' => true]);

    foreach ([[$marketing, CrmGroupMember::ROLE_MARKETING], [$sales, CrmGroupMember::ROLE_SALES]] as [$user, $role]) {
        CrmGroupMember::updateOrCreate(
            ['crm_group_id' => $demoGroup->id, 'user_id' => $user->id],
            ['role' => $role, 'is_active' => true]
        );
    }

    $demoLead = SalesLead::where('name', '[PAGE TEST] Demo Lead')->first();
    if (! $demoLead) {
        $demoLead = CrmLeadService::createLead([
            'name' => '[PAGE TEST] Demo Lead',
            'email' => 'page-test-lead@hesetak.test',
            'phone' => '01099002999',
            'source' => SalesLead::SOURCE_WEBSITE,
            'interested_advanced_course_id' => $course->id,
            'crm_group_id' => $demoGroup->id,
        ], $marketing);
        $createdDemo = true;
    }

    if ($demoLead->status === SalesLead::STATUS_NEW) {
        CrmLeadService::assignToSales($demoLead, $sales, $admin, $demoGroup->id);
        $demoLead = $demoLead->fresh();
        foreach ([
            SalesLead::STATUS_CONTACTED,
            SalesLead::STATUS_INTERESTED,
            SalesLead::STATUS_PLACEMENT_TEST,
            SalesLead::STATUS_OFFER_SENT,
            SalesLead::STATUS_PAYMENT_PENDING,
        ] as $status) {
            CrmLeadService::transitionStatus($demoLead->fresh(), $status, $sales);
            $demoLead = $demoLead->fresh();
        }
    }
}

$leadId = $demoLead?->id ?? SalesLead::latest()->value('id') ?? 0;
$groupId = $demoGroup?->id ?? CrmGroup::latest()->value('id') ?? 0;

$pages = [];

if ($admin) {
    foreach ([
        '/admin/crm',
        '/admin/crm/leads',
        '/admin/crm/commissions',
        '/admin/crm/audit',
        '/admin/crm/groups',
        '/admin/crm/groups/create',
        '/admin/crm/reports',
        '/admin/employee-jobs',
        '/admin/orders',
    ] as $uri) {
        $pages[] = ['admin', $admin, $uri, true];
    }
    if ($leadId) {
        $pages[] = ['admin', $admin, '/admin/crm/leads/'.$leadId, true];
        $pages[] = ['admin', $admin, '/admin/crm/leads?status=payment_pending', true];
        $pages[] = ['admin', $admin, '/admin/crm/audit?action=lead_created', true];
        $pages[] = ['admin', $admin, '/admin/crm/commissions?status=pending', true];
    }
    if ($groupId) {
        $pages[] = ['admin', $admin, '/admin/crm/groups/'.$groupId.'/edit', true];
    }
}

$employeeRoles = [
    'marketing' => $marketing,
    'sales' => $sales,
    'team_leader' => $teamLeader,
    'finance' => $finance,
];

foreach ($employeeRoles as $role => $user) {
    if (! $user) {
        continue;
    }
    foreach ([
        '/employee/crm',
        '/employee/crm/leads',
        '/employee/crm/commissions',
        '/employee/crm/messages',
    ] as $uri) {
        $pages[] = [$role, $user, $uri, true];
    }
    if (in_array($role, ['marketing', 'sales', 'team_leader'], true)) {
        $pages[] = [$role, $user, '/employee/crm/reports', true];
        $pages[] = [$role, $user, '/employee/crm/reports/create?type=weekly', true];
    }
    if ($role === 'team_leader') {
        $pages[] = [$role, $user, '/employee/crm/team', true];
    }
    if ($role === 'sales') {
        $pages[] = [$role, $user, '/employee/sales/desk', true];
    }
    $pages[] = [$role, $user, '/employee/crm/leads/create', $role === 'marketing'];
    if ($role === 'finance') {
        $pages[] = [$role, $user, '/employee/crm/sales-financial', true];
        $pages[] = [$role, $user, '/employee/crm/team', true];
    }
    if ($leadId) {
        $pages[] = [$role, $user, '/employee/crm/leads/'.$leadId, true];
        $pages[] = [$role, $user, '/employee/crm/leads?status=payment_pending', true];
    }
}

// صفحات متوقع منعها — يجب 403 بدون أخطاء PHP
if ($sales) {
    $pages[] = ['sales', $sales, '/employee/crm/leads/create', false];
}

$failures = [];
$passed = 0;

foreach ($pages as [$role, $user, $uri, $expectOk]) {
    $result = fetchPage($kernel, $uri, $user);
    $code = $result['code'];
    $body = $result['body'];

    if ($result['exception']) {
        $failures[] = ['role' => $role, 'uri' => $uri, 'http' => $code, 'issue' => 'exception: '.$result['exception']];
        continue;
    }

    if ($code >= 500) {
        $failures[] = ['role' => $role, 'uri' => $uri, 'http' => $code, 'issue' => 'HTTP '.$code];
        continue;
    }

    $contentError = bodyHasError($body, $errorPatterns);
    if ($contentError) {
        $failures[] = ['role' => $role, 'uri' => $uri, 'http' => $code, 'issue' => 'محتوى خطأ: '.$contentError];
        continue;
    }

    if ($expectOk && $code >= 400) {
        $failures[] = ['role' => $role, 'uri' => $uri, 'http' => $code, 'issue' => 'متوقع 200 لكن حصل '.$code];
        continue;
    }

    if (! $expectOk && $code < 400) {
        $failures[] = ['role' => $role, 'uri' => $uri, 'http' => $code, 'issue' => 'متوقع 403 لكن حصل '.$code];
        continue;
    }

    $passed++;
}

$out = [
    'ok' => empty($failures),
    'passed' => $passed,
    'total' => count($pages),
    'failures' => $failures,
    'demo_lead_id' => $leadId,
    'demo_group_id' => $groupId,
    'demo_created_now' => $createdDemo,
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit(empty($failures) ? 0 : 1);
