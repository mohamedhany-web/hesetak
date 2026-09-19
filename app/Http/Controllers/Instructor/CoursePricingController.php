<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * تسعير الكورسات المسجّلة من المعلم بالريال السعودي.
 */
class CoursePricingController extends Controller
{
    public function edit(AdvancedCourse $course): View
    {
        $this->assertOwns($course);

        return view('instructor.courses.pricing', compact('course'));
    }

    public function update(Request $request, AdvancedCourse $course): RedirectResponse
    {
        $this->assertOwns($course);

        $data = $request->validate([
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'price_usd_after_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $usd = $data['price_usd'] ?? null;
        $usdSale = $data['price_usd_after_discount'] ?? null;

        $payload = [
            'price_usd' => $usd,
            'price_usd_after_discount' => $usdSale,
        ];

        // مزامنة الحقول القديمة + توافق price_egp مع الريال
        if ($usd !== null && $usd !== '') {
            $payload['price'] = $usd;
            $payload['price_after_discount'] = $usdSale;
            $payload['price_egp'] = $usd;
            $payload['price_egp_after_discount'] = $usdSale;
        }

        if (Schema::hasColumn('advanced_courses', 'price_usd')) {
            $course->update($payload);
        } else {
            $course->update([
                'price' => $payload['price'] ?? $course->price,
                'price_after_discount' => $payload['price_after_discount'] ?? $course->price_after_discount,
            ]);
        }

        return back()->with('success', 'تم تحديث أسعار الكورس بالريال السعودي.');
    }

    private function assertOwns(AdvancedCourse $course): void
    {
        $user = request()->user();
        $ids = $user->teachingAdvancedCourseIds();
        abort_unless($ids->contains((int) $course->id), 403);
    }
}
