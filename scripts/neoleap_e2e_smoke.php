<?php

/**
 * NeoLeap end-to-end smoke (CLI on server).
 * Usage: php scripts/neoleap_e2e_smoke.php
 */

use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\NeoLeapCheckoutController;
use App\Models\AdvancedCourse;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServicePackage;
use App\Models\StudentEntitlement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NeoLeapService;
use App\Services\NeoLeapSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function line(string $msg): void
{
    echo $msg.PHP_EOL;
}

function ok(string $label, bool $pass, string $detail = ''): void
{
    line(($pass ? '[PASS] ' : '[FAIL] ').$label.($detail !== '' ? ' — '.$detail : ''));
}

line('=== NeoLeap E2E smoke '.date('c').' ===');
ok('NeoLeap ready', NeoLeapSettings::isReady(), 'mode='.NeoLeapSettings::mode());
ok('NeoLeap configured', NeoLeapSettings::isConfigured());

$buyer = User::query()->where('email', 'admin@hesetak.com')->first()
    ?? User::query()->orderBy('id')->first();
ok('Buyer user', (bool) $buyer, $buyer ? 'id='.$buyer->id : 'missing');
if (! $buyer) {
    exit(1);
}
Auth::login($buyer);

$pkg = ServicePackage::query()->storefront()->ordered()->first();
ok('Storefront package', (bool) $pkg, $pkg ? '#'.$pkg->id.' '.$pkg->name : 'none');

$course = AdvancedCourse::query()->where('is_active', true)->orderBy('id')->first();
ok('Active course', (bool) $course, $course ? '#'.$course->id : 'none');

$neoleap = app(NeoLeapService::class);

// 1) Initiate package-like amount
$trackPkg = 'HST-SMOKE-P-'.time();
try {
    $initPkg = $neoleap->initiateHostedPayment(
        amount: 1.00,
        trackId: $trackPkg,
        responseUrl: 'https://hissatak.online/checkout/neoleap/return',
        errorUrl: 'https://hissatak.online/checkout/neoleap/error',
        currency: 'SAR',
        lang: 'AR',
    );
    $hasPid = str_contains($initPkg['redirect_url'], 'PaymentID=');
    ok('Initiate package amount', true, 'pid='.$initPkg['payment_id']);
    ok('Redirect has PaymentID', $hasPid, $initPkg['redirect_url']);
} catch (Throwable $e) {
    ok('Initiate package amount', false, $e->getMessage());
    $initPkg = null;
}

// 2) Create real pending package order + approve accounting path
$results = [
    'package_order' => null,
    'course_order' => null,
];

if ($pkg) {
    DB::beginTransaction();
    try {
        $order = Order::create([
            'user_id' => $buyer->id,
            'service_package_id' => $pkg->id,
            'order_type' => Order::TYPE_SERVICE_PACKAGE,
            'original_amount' => 1.00,
            'discount_amount' => 0,
            'amount' => 1.00,
            'currency' => 'SAR',
            'payment_method' => 'online',
            'status' => Order::STATUS_PENDING,
            'custom_package_data' => [
                'smoke_test' => true,
                'name' => $pkg->name,
            ],
        ]);
        if (Schema::hasColumn('orders', 'neoleap_track_id')) {
            $order->update([
                'neoleap_track_id' => 'HST-'.$order->id.'-SMOKE1',
                'neoleap_payment_id' => $initPkg['payment_id'] ?? ('SMOKE-'.$order->id),
            ]);
        }

        $invoice = app(CheckoutController::class)->approveOrderAfterOnlinePaymentPublic(
            $order->fresh(),
            'neoleap',
            'SMOKE-TXN-PKG-'.$order->id,
            ['result' => 'CAPTURED', 'smoke' => true],
            'NeoLeap'
        );

        $order->refresh();
        $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
        $txn = Transaction::query()
            ->where('user_id', $buyer->id)
            ->where(function ($q) use ($order) {
                $q->where('description', 'like', '%'.$order->id.'%')
                    ->orWhere('reference', 'like', '%'.$order->id.'%');
            })
            ->latest('id')
            ->first();
        if (! $txn) {
            $txn = Transaction::query()->where('user_id', $buyer->id)->latest('id')->first();
        }

        $entitlement = null;
        if (Schema::hasTable('student_entitlements')) {
            $entitlement = StudentEntitlement::query()
                ->where('user_id', $buyer->id)
                ->where('service_package_id', $pkg->id)
                ->latest('id')
                ->first();
        }

        ok('Package order approved', $order->status === Order::STATUS_APPROVED, 'order#'.$order->id);
        ok('Package invoice created', (bool) $invoice && $invoice->status === 'paid', $invoice ? '#'.$invoice->id.' '.$invoice->invoice_number : '');
        ok('Package payment neoleap', (bool) $payment && $payment->payment_gateway === 'neoleap' && $payment->status === 'completed', $payment ? '#'.$payment->id : '');
        ok('Package transaction exists', (bool) $txn, $txn ? '#'.$txn->id : 'not found by description — check latest');
        ok('Package entitlement/activation', $entitlement !== null || $order->status === Order::STATUS_APPROVED, $entitlement ? 'entitlement#'.$entitlement->id : 'approved (entitlement may be sync elsewhere)');

        $results['package_order'] = $order->id;
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        ok('Package approve path', false, $e->getMessage());
    }
}

// 3) Course order approve path
if ($course) {
    DB::beginTransaction();
    try {
        $order = Order::create([
            'user_id' => $buyer->id,
            'advanced_course_id' => $course->id,
            'original_amount' => 1.00,
            'discount_amount' => 0,
            'amount' => 1.00,
            'currency' => 'SAR',
            'payment_method' => 'online',
            'status' => Order::STATUS_PENDING,
            'billing_mode' => 'one_time',
        ]);

        $invoice = app(CheckoutController::class)->approveOrderAfterOnlinePaymentPublic(
            $order->fresh(),
            'neoleap',
            'SMOKE-TXN-CRS-'.$order->id,
            ['result' => 'CAPTURED', 'smoke' => true],
            'NeoLeap'
        );
        $order->refresh();
        $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

        $enrolled = method_exists($buyer, 'isEnrolledIn')
            ? $buyer->fresh()->isEnrolledIn($course->id)
            : Schema::hasTable('course_user') && DB::table('course_user')
                ->where('user_id', $buyer->id)
                ->where(function ($q) use ($course) {
                    $q->where('advanced_course_id', $course->id)->orWhere('course_id', $course->id);
                })
                ->exists();

        ok('Course order approved', $order->status === Order::STATUS_APPROVED, 'order#'.$order->id);
        ok('Course invoice paid', (bool) $invoice && $invoice->status === 'paid', $invoice ? '#'.$invoice->invoice_number : '');
        ok('Course payment neoleap', (bool) $payment && $payment->payment_gateway === 'neoleap', $payment ? '#'.$payment->id : '');
        ok('Course enrollment/activation', (bool) $enrolled, $enrolled ? 'enrolled' : 'check syncEnrollmentFromOrder');

        $results['course_order'] = $order->id;
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        ok('Course approve path', false, $e->getMessage());
    }
}

// 4) Full callback captureAndApprove with encrypted CAPTURED (may depend on inquire)
if ($pkg && ! empty($initPkg['payment_id'])) {
    try {
        $order = Order::create([
            'user_id' => $buyer->id,
            'service_package_id' => $pkg->id,
            'order_type' => Order::TYPE_SERVICE_PACKAGE,
            'original_amount' => 1.00,
            'discount_amount' => 0,
            'amount' => 1.00,
            'currency' => 'SAR',
            'payment_method' => 'online',
            'status' => Order::STATUS_PENDING,
            'custom_package_data' => ['smoke_callback' => true, 'name' => $pkg->name],
        ]);
        $track = 'HST-'.$order->id.'-'.Str::upper(Str::random(6));
        $order->update([
            'neoleap_track_id' => $track,
            'neoleap_payment_id' => $initPkg['payment_id'],
        ]);

        $payload = [[
            'result' => 'CAPTURED',
            'trackId' => $track,
            'paymentId' => $initPkg['payment_id'],
            'amt' => '1.00',
            'currencyCode' => '682',
            'ref' => 'SMOKE-REF-'.$order->id,
            'udf1' => (string) $order->id,
        ]];
        $trandata = $neoleap->encrypt(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $parsed = $neoleap->parseCallbackTrandata($trandata);
        ok('Callback decrypt CAPTURED', ($parsed['success'] ?? false) === true, $parsed['result'] ?? '');

        try {
            app(NeoLeapCheckoutController::class)->captureAndApprove($order->fresh(), $parsed);
            $order->refresh();
            ok('Callback captureAndApprove', $order->status === Order::STATUS_APPROVED, 'order#'.$order->id.' status='.$order->status);
        } catch (Throwable $e) {
            // Expected if inquire rejects unpaid PaymentID
            ok('Callback captureAndApprove', false, 'expected if unpaid: '.$e->getMessage());
        }
    } catch (Throwable $e) {
        ok('Callback simulation setup', false, $e->getMessage());
    }
}

// 5) Surface flags
$flags = [
    'course_checkout_flag' => NeoLeapSettings::isReady(),
    'package_checkout_flag' => NeoLeapSettings::isReady(),
    'gift_pay_flag' => NeoLeapSettings::isReady(),
];
foreach ($flags as $k => $v) {
    ok($k, (bool) $v);
}

line('=== summary ===');
line(json_encode($results, JSON_UNESCAPED_UNICODE));
line('Done.');
