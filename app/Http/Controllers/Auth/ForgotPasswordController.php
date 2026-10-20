<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    /**
     * عرض نموذج طلب رابط إعادة تعيين كلمة المرور
     */
    public function showLinkRequestForm(Request $request)
    {
        $authBackgroundUrl = \App\Providers\AppServiceProvider::authBackgroundUrl();
        $sent = $request->boolean('sent') || filled(session('status'));
        $sentEmail = session('reset_email', old('email'));

        return view('auth.forgot-password', compact('authBackgroundUrl', 'sent', 'sentEmail'));
    }

    /**
     * إرسال رابط إعادة التعيين إلى البريد المسجل (مع التحقق من وجود البريد)
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->only('email'), [
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => __('auth.email_required'),
            'email.email' => __('auth.email_invalid'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = (string) $request->input('email');
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if (! $user) {
            return back()->withErrors(['email' => __('auth.email_not_registered')])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => __('auth.account_inactive')])->withInput();
        }

        try {
            // أنشئ التوكن فوراً، وأرسل الميل بعد إرسال الرد حتى لا تعلق الصفحة على SMTP.
            $status = Password::broker()->sendResetLink(
                ['email' => $user->email],
                function (User $user, string $token): void {
                    dispatch(static function () use ($user, $token): void {
                        $user->sendPasswordResetNotification($token);
                    })->afterResponse();
                }
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => __('auth.reset_link_failed')])->withInput();
        }

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()
                ->route('password.request', ['sent' => 1])
                ->with('status', __('auth.reset_link_sent'))
                ->with('reset_email', $user->email);
        }

        if ($status === Password::RESET_THROTTLED) {
            return redirect()
                ->route('password.request', ['sent' => 1])
                ->with('status', __('auth.reset_link_throttled_soft'))
                ->with('reset_email', $user->email);
        }

        return back()->withErrors(['email' => __('auth.reset_link_failed')])->withInput();
    }
}
