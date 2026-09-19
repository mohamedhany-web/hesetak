<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TutoringGroupBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * حجوزات المجموعات — معطّلة في حصتك (فردي 1:1 فقط).
 */
class TutoringBookingController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('student.learn.index', ['tab' => 'private']);
    }

    public function show(Request $request, TutoringGroupBooking $booking): RedirectResponse
    {
        return redirect()->route('student.learn.index', ['tab' => 'private']);
    }

    public function bookFromSubscription(Request $request): RedirectResponse
    {
        return redirect()
            ->route('student.learn.index', ['tab' => 'private'])
            ->with('error', app()->getLocale() === 'ar'
                ? 'حجز المجموعات غير متاح. استخدم الحصص الفردية.'
                : 'Group booking is unavailable. Use private 1:1 lessons.');
    }

    public function bookFromEntitlement(Request $request): RedirectResponse
    {
        return redirect()
            ->route('student.learn.index', ['tab' => 'private'])
            ->with('error', app()->getLocale() === 'ar'
                ? 'حجز المجموعات غير متاح. استخدم الحصص الفردية.'
                : 'Group booking is unavailable. Use private 1:1 lessons.');
    }
}
