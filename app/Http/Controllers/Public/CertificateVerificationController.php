<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    public function verify(Request $request, $code = null)
    {
        $verificationCode = $code ?? $request->input('code');
        $pageMeta = [
            'pageTitle' => (app()->getLocale() === 'ar' ? 'تحقق من شهادة' : 'Verify certificate').' — '.__('landing.nav.brand'),
            'pageDescription' => app()->getLocale() === 'ar'
                ? 'تحقق من صحة شهادات المنصة عبر رمز التحقق أو الرقم التسلسلي.'
                : 'Verify platform certificates with a code or serial number.',
            'mcActive' => '',
        ];

        if (! $verificationCode) {
            return view('public.certificates.verify', array_merge($pageMeta, [
                'certificate' => null,
                'error' => app()->getLocale() === 'ar' ? 'الرجاء إدخال رمز التحقق' : 'Please enter a verification code',
            ]));
        }

        $certificate = Certificate::query()
            ->where('verification_code', $verificationCode)
            ->orWhere('serial_number', $verificationCode)
            ->with(['user', 'course', 'instructor'])
            ->first();

        if (! $certificate) {
            return view('public.certificates.verify', array_merge($pageMeta, [
                'certificate' => null,
                'error' => app()->getLocale() === 'ar'
                    ? 'الشهادة غير موجودة أو رمز التحقق غير صحيح'
                    : 'Certificate not found or verification code is invalid',
            ]));
        }

        $isValid = true;
        if ($certificate->certificate_hash) {
            $isValid = $certificate->verifyHash();
        }

        return view('public.certificates.verify', array_merge($pageMeta, [
            'certificate' => $certificate,
            'isValid' => $isValid,
            'error' => $isValid
                ? null
                : (app()->getLocale() === 'ar' ? 'تم اكتشاف تلاعب في الشهادة' : 'Certificate integrity check failed'),
        ]));
    }
}
