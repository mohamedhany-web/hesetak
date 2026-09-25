<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\PublicMediaStorage;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * عرض طلبات الطالب
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'pending', 'approved', 'rejected'], true)) {
            $filter = 'all';
        }

        $base = Order::query()->where('user_id', auth()->id());

        $counts = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', Order::STATUS_PENDING)->count(),
            'approved' => (clone $base)->where('status', Order::STATUS_APPROVED)->count(),
            'rejected' => (clone $base)->where('status', Order::STATUS_REJECTED)->count(),
        ];

        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with(['course.academicSubject', 'course.academicYear', 'learningPath', 'servicePackage'])
            ->when($filter !== 'all', fn ($query) => $query->where('status', $filter))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('notes', 'like', '%'.$q.'%')
                        ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', '%'.$q.'%'))
                        ->orWhereHas('servicePackage', fn ($pq) => $pq->where('name', 'like', '%'.$q.'%'))
                        ->orWhereHas('learningPath', fn ($lq) => $lq->where('name', 'like', '%'.$q.'%'));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('student.orders.index', [
            'orders' => $orders,
            'searchQuery' => $q,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    /**
     * إنشاء طلب جديد
     */
    public function store(Request $request, AdvancedCourse $advancedCourse)
    {
        $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash,other',
            'wallet_id' => [
                'nullable',
                'required_if:payment_method,bank_transfer',
                Rule::exists('wallets', 'id')->where('is_active', true)->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer']),
            ],
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string|max:500',
        ], [
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'wallet_id.required_if' => 'يجب اختيار حساب التحويل على المنصة حتى يُسجَّل المبلغ على المحفظة عند الموافقة.',
            'wallet_id.exists' => 'المحفظة المختارة غير صالحة أو غير متاحة.',
            'payment_proof.required' => 'صورة الإيصال مطلوبة',
            'payment_proof.image' => 'يجب أن يكون الملف صورة',
            'payment_proof.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png أو jpg',
            'payment_proof.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        // التحقق من عدم وجود طلب مقبول مسبق
        $existingApprovedOrder = Order::where('user_id', auth()->id())
            ->where('advanced_course_id', $advancedCourse->id)
            ->where('status', Order::STATUS_APPROVED)
            ->exists();

        if ($existingApprovedOrder) {
            return back()->with('error', 'أنت مسجل بالفعل في هذا الكورس');
        }

        // التحقق من وجود طلب في الانتظار
        $existingPendingOrder = Order::where('user_id', auth()->id())
            ->where('advanced_course_id', $advancedCourse->id)
            ->where('status', Order::STATUS_PENDING)
            ->exists();

        if ($existingPendingOrder) {
            return back()->with('error', 'لديك طلب في الانتظار لهذا الكورس');
        }

        // رفع صورة الإيصال
        $paymentProofPath = PublicMediaStorage::storeFile($request->file('payment_proof'), 'payment-proofs');

        // حساب السعر النهائي (بعد خصم الإحالة إذا كان موجوداً)
        $originalAmount = $advancedCourse->effectivePurchasePrice();
        $finalAmount = $originalAmount;
        $discountAmount = 0;
        $referralCoupon = null;

        // تطبيق خصم الإحالة تلقائياً
        $referralService = app(ReferralService::class);
        $referralCoupon = $referralService->applyReferralDiscount(auth()->user(), $originalAmount);

        if ($referralCoupon) {
            $discountAmount = $referralCoupon->calculateDiscount($originalAmount);
            $finalAmount = $originalAmount - $discountAmount;

            // زيادة عدد مرات استخدام الخصم
            $referral = \App\Models\Referral::where('auto_coupon_id', $referralCoupon->id)->first();
            if ($referral) {
                $referral->incrementDiscountUsage();
                $referral->update(['discount_amount' => $discountAmount]);
            }
        }

        $couponId = null;
        $couponDiscountAmount = 0;
        $appliedCoupon = null;

        // التحقق من كوبون يدوي إذا كان موجوداً (يُسجَّل الاستخدام بعد إنشاء الطلب لربط order_id)
        if ($request->filled('applied_coupon_id')) {
            $coupon = Coupon::find($request->applied_coupon_id);
            if ($coupon && $coupon->isValid() && $coupon->canBeUsedByUser(auth()->id()) && $coupon->appliesToAdvancedCourseId((int) $advancedCourse->id)) {
                $couponDiscountAmount = $coupon->calculateDiscount($finalAmount);
                if ($couponDiscountAmount > 0) {
                    $discountAmount += $couponDiscountAmount;
                    $finalAmount -= $couponDiscountAmount;
                    $couponId = $coupon->id;
                    $appliedCoupon = $coupon;
                    $appliedCoupon->incrementUsage();
                }
            }
        }

        // إنشاء الطلب
        $orderData = [
            'user_id' => auth()->id(),
            'advanced_course_id' => $advancedCourse->id,
            'coupon_id' => $couponId,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'amount' => $finalAmount, // السعر النهائي بعد الخصم
            'payment_method' => $request->payment_method,
            'payment_proof' => $paymentProofPath,
            'notes' => $request->notes ?? '',
            'status' => Order::STATUS_PENDING,
        ];

        // إضافة ملاحظة عن الخصومات
        $discountNotes = [];
        if ($referralCoupon && isset($discountAmount)) {
            $referralDiscountAmount = $discountAmount - $couponDiscountAmount;
            if ($referralDiscountAmount > 0) {
                $discountNotes[] = 'خصم الإحالة: '.number_format($referralDiscountAmount, 2) . ' ' . currency_symbol();
            }
        }
        if ($couponDiscountAmount > 0) {
            $discountNotes[] = 'خصم الكوبون ('.($appliedCoupon->code ?? '').'): '.number_format($couponDiscountAmount, 2) . ' ' . currency_symbol();
        }
        if (! empty($discountNotes)) {
            $orderData['notes'] .= (! empty($orderData['notes']) ? "\n" : '').implode("\n", $discountNotes);
        }

        $orderData['wallet_id'] = $request->payment_method === 'bank_transfer' ? $request->wallet_id : null;

        $order = Order::create($orderData);

        if ($appliedCoupon && $couponDiscountAmount > 0) {
            \App\Models\CouponUsage::create([
                'coupon_id' => $appliedCoupon->id,
                'user_id' => auth()->id(),
                'order_id' => $order->id,
                'discount_amount' => $couponDiscountAmount,
                'order_amount' => $originalAmount,
                'final_amount' => $order->amount,
            ]);
        }

        return back()->with('success', 'تم إرسال طلبك بنجاح! سيتم مراجعته قريباً');
    }

    /**
     * عرض تفاصيل الطلب
     */
    public function show(Order $order)
    {
        // التأكد من أن الطلب يخص الطالب الحالي
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'course.academicSubject',
            'course.academicYear',
            'learningPath',
            'approver',
            'invoice',
            'payment',
            'servicePackage',
        ]);

        return view('student.orders.show', compact('order'));
    }
}
