<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fail = 0;
$pass = 0;

function assertTrue(bool $cond, string $label): void
{
    global $fail, $pass;
    if ($cond) {
        echo "OK  {$label}\n";
        $pass++;
    } else {
        echo "FAIL {$label}\n";
        $fail++;
    }
}

$user = App\Models\User::where('email', 'crm-test-sales@glottical.test')->first();
assertTrue((bool) $user, 'sales test user exists');
if (! $user) {
    echo "Aborted.\n";
    exit(1);
}

assertTrue($user->employeeCan('sales_desk'), 'employeeCan sales_desk');
assertTrue($user->employeeCan('manage.orders'), 'employeeCan manage.orders (sidebar alias)');
assertTrue($user->employeeCan('sales_orders'), 'employeeCan sales_orders');
assertTrue(App\Services\Crm\CrmAccessService::canAccessCrm($user), 'CRM access');

$casts = (new App\Models\Order)->getCasts();
assertTrue(($casts['sales_contacted_at'] ?? null) === 'datetime', 'Order casts sales_contacted_at');

Auth::login($user);
view()->share('errors', new Illuminate\Support\ViewErrorBag);
$ctrl = app(App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class);

$desk = $ctrl->desk();
assertTrue($desk instanceof Illuminate\View\View && $desk->name() === 'employee.sales.desk', 'desk view');
$deskData = $desk->getData();
assertTrue(! empty($deskData['useCrm']), 'desk useCrm');
assertTrue(($deskData['leadsIndexRoute'] ?? '') === 'employee.crm.leads.index', 'desk CRM leads route');
assertTrue(isset($deskData['stats']['mine_won_month'], $deskData['stats']['rejected']), 'desk stats complete');

$html = $desk->render();
assertTrue(str_contains($html, 'لوحة المبيعات'), 'desk renders Arabic title');
assertTrue(str_contains($html, 'لوحة CRM') || str_contains($html, 'CRM حصتك'), 'desk CRM CTA');

$reqBoth = Illuminate\Http\Request::create('/employee/sales/orders', 'GET', ['mine' => 1, 'unassigned' => 1]);
$ordersView = $ctrl->ordersIndex($reqBoth);
assertTrue($ordersView instanceof Illuminate\View\View, 'orders index view');
$orders = $ordersView->getData()['orders'];
foreach ($orders as $o) {
    if ((int) $o->sales_owner_id !== (int) $user->id) {
        assertTrue(false, 'mine+unassigned prefers mine only');
        break;
    }
}
assertTrue(true, 'orders mine+unassigned filter prefers mine');

$order = App\Models\Order::query()->first();
if ($order) {
    $order->forceFill(['sales_contacted_at' => now()])->save();
    $order->refresh();
    assertTrue($order->sales_contacted_at instanceof Carbon\CarbonInterface, 'sales_contacted_at is Carbon');
    $show = $ctrl->orderShow($order);
    assertTrue($show->name() === 'employee.sales.order-show', 'order show view');
    $showHtml = $show->render();
    assertTrue(str_contains($showHtml, 'آخر نشاط مسجل') || ! $order->sales_contacted_at, 'order show contacted label');
} else {
    echo "SKIP no orders in DB\n";
}

$leadCtrl = app(App\Http\Controllers\Employee\EmployeeSalesLeadController::class);
$leadIndex = $leadCtrl->index(Illuminate\Http\Request::create('/employee/sales/leads', 'GET'));
assertTrue($leadIndex instanceof Illuminate\Http\RedirectResponse, 'CRM user redirected from legacy leads index');

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
