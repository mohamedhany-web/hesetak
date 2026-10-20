<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CrmGroup;
use App\Models\User;
use App\Services\Crm\CrmAccessService;

$pass = 0;
$fail = 0;
function check(bool $ok, string $label): void
{
    global $pass, $fail;
    echo ($ok ? 'OK  ' : 'FAIL ').$label."\n";
    $ok ? $pass++ : $fail++;
}

// TL with job + group
$tl = User::where('email', 'crm-page-tl@hesetak.test')->first();
check((bool) $tl, 'page TL exists');
if ($tl) {
    check(CrmAccessService::crmRole($tl) === 'team_leader', 'page TL role=team_leader');
    check(CrmAccessService::hasCrmPermission($tl, 'crm_assign_leads'), 'page TL has assign');
    check(CrmAccessService::assignableSalesUsers($tl)->isNotEmpty(), 'page TL has sales users');
    check(CrmAccessService::canAssignLead($tl), 'page TL canAssign');
}

// TL without group
$tl2 = User::where('email', 'crm-test-tl@hesetak.test')->first();
if ($tl2) {
    check(CrmAccessService::canAssignLead($tl2) === false, 'ungrouped TL cannot assign');
    check(CrmAccessService::assignableSalesUsers($tl2)->isEmpty(), 'ungrouped TL no sales list');
}

// Simulate sales employee appointed as group leader
$sales = User::where('email', 'crm-test-sales@hesetak.test')->first()
    ?? User::where('email', 'crm-page-sales@hesetak.test')->first();
$group = CrmGroup::query()->where('is_active', true)->first();
if ($sales && $group) {
    $originalTl = $group->team_leader_id;
    $group->update(['team_leader_id' => $sales->id]);
    $sales->refresh();
    check(CrmAccessService::leadsActiveCrmGroupsAsLeader($sales), 'sales user marked as group leader');
    check(CrmAccessService::crmRole($sales) === 'team_leader', 'sales job + group leadership => team_leader role');
    check(CrmAccessService::hasCrmPermission($sales, 'crm_assign_leads'), 'group leader gets assign permission');
    check(CrmAccessService::canAssignLead($sales), 'group leader canAssign');
    $group->update(['team_leader_id' => $originalTl]);
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
