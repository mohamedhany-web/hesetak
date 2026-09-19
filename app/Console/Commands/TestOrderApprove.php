<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\LearningPathEnrollment;
use App\Models\StudentCourseEnrollment;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * تشغيل خطوات الموافقة على الطلب لاكتشاف سبب الخطأ
 * الاستخدام: php artisan order:test-approve 1
 */
class TestOrderApprove extends Command
{
    protected $signature = 'order:test-approve {order_id=1}';
    protected $description = 'Run order approval steps to find the exact error';

    public function handle(): int
    {
        $orderId = (int) $this->argument('order_id');
        $this->info("Loading order {$orderId}...");

        $order = Order::find($orderId);
        if (!$order) {
            $this->error("Order {$orderId} not found.");
            return 1;
        }

        $admin = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->first()
            ?? \App\Models\User::first();
        if (!$admin) {
            $this->error("No admin user found.");
            return 1;
        }
        Auth::login($admin);

        DB::beginTransaction();
        try {
            $order->refresh();
            if ($order->status !== Order::STATUS_PENDING) {
                throw new \Exception('Order status is not pending: ' . $order->status);
            }
            if ($order->invoice_id) {
                throw new \Exception('Order already has invoice_id');
            }
            $this->info("Step 1: Order checks OK");

            $order->load(['learningPath', 'course']);
            $isLearningPath = !empty($order->academic_year_id);
            $orderTitle = $isLearningPath
                ? ($order->learningPath?->name ?? 'مسار تعليمي (محذوف)')
                : ($order->course?->title ?? 'كورس (محذوف أو غير موجود)');
            $orderType = $isLearningPath ? 'learning_path' : 'course';
            $this->info("Step 2: Order type OK (isLearningPath={$isLearningPath}, type={$orderType})");

            $this->info("Step 3: Creating Invoice...");
            $invoiceNumber = 'INV-' . str_pad(Invoice::count() + 1, 8, '0', STR_PAD_LEFT);
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $order->user_id,
                'type' => $orderType,
                'description' => ($isLearningPath ? 'فاتورة تسجيل في المسار التعليمي: ' : 'فاتورة تسجيل في الكورس: ') . $orderTitle,
                'subtotal' => $order->amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $order->amount,
                'status' => 'paid',
                'due_date' => now(),
                'paid_at' => now(),
                'notes' => 'فاتورة مسبقة الدفع - من طلب رقم: ' . $order->id,
                'items' => [
                    [
                        'description' => ($isLearningPath ? 'تسجيل في المسار التعليمي: ' : 'تسجيل في الكورس: ') . $orderTitle,
                        'quantity' => 1,
                        'price' => $order->amount,
                        'total' => $order->amount,
                    ]
                ],
            ]);
            $this->info("  Invoice created: " . $invoice->invoice_number);

            $this->info("Step 4: Creating Payment...");
            $paymentMethodMap = ['bank_transfer' => 'bank_transfer', 'cash' => 'cash', 'other' => 'other'];
            $paymentMethod = $paymentMethodMap[$order->payment_method] ?? 'other';
            $paymentNumber = Payment::generateUniquePaymentNumber();
            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'user_id' => $order->user_id,
                'payment_method' => $paymentMethod,
                'amount' => $order->amount,
                'currency' => platform_currency(),
                'status' => 'completed',
                'paid_at' => now(),
                'processed_by' => auth()->id(),
                'notes' => 'دفعة من طلب رقم: ' . $order->id,
            ]);
            $this->info("  Payment created: " . $payment->payment_number);

            $this->info("Step 5: Creating Transaction...");
            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateUniqueTransactionNumber(),
                'user_id' => $order->user_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'expense_id' => null,
                'subscription_id' => null,
                'type' => 'credit',
                'category' => 'course_payment',
                'amount' => $order->amount,
                'currency' => platform_currency(),
                'description' => 'دفعة مقابل تسجيل - طلب رقم: ' . $order->id . ' - فاتورة: ' . $invoice->invoice_number,
                'status' => 'completed',
                'metadata' => [
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'course_id' => $order->advanced_course_id,
                    'academic_year_id' => $order->academic_year_id,
                    'wallet_id' => $order->wallet_id,
                ],
                'created_by' => auth()->id(),
            ]);
            $this->info("  Transaction created: " . $transaction->transaction_number);

            $this->info("Step 6: Updating Order...");
            $order->update([
                'status' => Order::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
            ]);
            $this->info("  Order updated to approved");

            if ($order->academic_year_id) {
                $this->info("Step 7: Learning path enrollment...");
                $existingPathEnrollment = LearningPathEnrollment::where('user_id', $order->user_id)
                    ->where('academic_year_id', $order->academic_year_id)
                    ->first();
                if (!$existingPathEnrollment) {
                    $pathEnrollment = LearningPathEnrollment::create([
                        'user_id' => $order->user_id,
                        'academic_year_id' => $order->academic_year_id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                        'activated_at' => now(),
                        'activated_by' => auth()->id(),
                        'progress' => 0,
                    ]);
                    $this->info("  LearningPathEnrollment created, calling enrollInPathCourses...");
                    $controller = app(\App\Http\Controllers\Admin\OrderController::class);
                    $method = new \ReflectionMethod($controller, 'enrollInPathCourses');
                    $method->setAccessible(true);
                    $method->invoke($controller, $pathEnrollment);
                }
                $this->info("  Step 7 OK");
            }

            $this->info("Step 8: ActivityLog (minimal new_values to avoid memory exhaustion)...");
            $order->refresh();
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'order_approved',
                'model_type' => 'Order',
                'model_id' => $order->id,
                'new_values' => [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'status' => $order->status,
                    'approved_at' => $order->approved_at ? $order->approved_at->format('Y-m-d H:i:s') : null,
                    'approved_by' => $order->approved_by,
                    'invoice_id' => $order->invoice_id,
                    'payment_id' => $order->payment_id,
                    'amount' => $order->amount,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
            ]);
            $this->info("  Step 8 OK");

            $this->info("\nAll steps completed successfully. Rolling back (test only).");
        } catch (\Throwable $e) {
            $this->error("\nFAILED: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            $this->line("\nTrace:\n" . $e->getTraceAsString());
            DB::rollBack();
            return 1;
        }
        DB::rollBack();
        return 0;
    }
}
