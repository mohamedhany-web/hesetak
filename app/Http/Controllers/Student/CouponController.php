<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * التحقق من صحة الكوبون وحساب الخصم
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'course_id' => 'required|exists:advanced_courses,id',
        ]);

        $couponCode = strtoupper(trim($request->coupon_code));
        $course = AdvancedCourse::findOrFail($request->course_id);
        $coursePrice = $course->effectivePurchasePrice();

        // البحث عن الكوبون
        $coupon = Coupon::where('code', $couponCode)->first();

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'الكوبون غير صحيح',
            ], 400);
        }

        // التحقق من صلاحية الكوبون
        if (! $coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'الكوبون منتهي الصلاحية أو غير نشط',
            ], 400);
        }

        // التحقق من صلاحية الكوبون للمستخدم
        if (! $coupon->canBeUsedByUser(auth()->id())) {
            return response()->json([
                'valid' => false,
                'message' => 'لا يمكنك استخدام هذا الكوبون',
            ], 400);
        }

        if (! $coupon->appliesToAdvancedCourseId((int) $course->id)) {
            return response()->json([
                'valid' => false,
                'message' => $coupon->applicable_to === 'subscriptions'
                    ? 'هذا الكوبون مخصص للاشتراكات وليس لتسجيل الكورسات'
                    : 'هذا الكوبون لا ينطبق على هذا الكورس',
            ], 400);
        }

        // التحقق من الحد الأدنى للمبلغ
        if ($coupon->minimum_amount && $coursePrice < $coupon->minimum_amount) {
            return response()->json([
                'valid' => false,
                'message' => 'الحد الأدنى لاستخدام هذا الكوبون هو '.number_format($coupon->minimum_amount, 2) . ' ' . currency_symbol(),
            ], 400);
        }

        // حساب الخصم
        $discountAmount = $coupon->calculateDiscount($coursePrice);
        $finalAmount = $coursePrice - $discountAmount;

        return response()->json([
            'valid' => true,
            'message' => 'تم تطبيق الكوبون بنجاح',
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title ?? $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'pricing' => [
                'original_price' => $coursePrice,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'discount_percentage' => $coursePrice > 0 ? round(($discountAmount / $coursePrice) * 100, 1) : 0,
            ],
        ]);
    }
}
