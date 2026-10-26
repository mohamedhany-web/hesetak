<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StudentExclusiveSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class GoogleAuthController extends Controller
{
    /**
     * توجيه المستخدم إلى صفحة تسجيل الدخول بـ Google
     */
    public function redirect()
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_register_google_only')]);
        }

        return app(SocialiteFactory::class)
            ->driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * معالجة callback من Google: إنشاء أو تسجيل دخول حساب طالب
     */
    public function callback()
    {
        try {
            $googleUser = app(SocialiteFactory::class)->driver('google')->user();
        } catch (\Exception $e) {
            \Log::warning('Google OAuth error', ['message' => $e->getMessage()]);

            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_register_google_only')]);
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $name = $googleUser->getName() ?: ($googleUser->getNickname() ?: explode('@', (string) $email)[0]);

        if (empty($email)) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.login_register_google_only')]);
        }

        $user = User::where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        }

        if (! $user) {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => $googleId,
                'role' => 'student',
                'is_active' => true,
            ];
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $payload['email_verified_at'] = now();
            }
            $user = User::create($payload);
        } else {
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $googleId;
            }
            if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if ($updates) {
                $user->update($updates);
            }
        }

        if (! $user->is_active) {
            return redirect()->route('login')
                ->withErrors(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        StudentExclusiveSession::claim($user, request()->session()->getId());
        if (Schema::hasColumn('users', 'last_login_at')) {
            $user->update(['last_login_at' => now()]);
        }

        $intended = session('url.intended');

        if ($user->isEmployee()) {
            return redirect()->intended(route('employee.dashboard'));
        }
        if ($user->role === 'super_admin' || $user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->isInstructor()) {
            if (! $user->canAccessInstructorPanel()) {
                return redirect()->route('public.tutor.apply.profile');
            }

            return redirect()->intended(route('dashboard'));
        }

        if ($intended && str_contains($intended, 'community') && $user->is_community_contributor) {
            session()->forget('url.intended');

            return redirect()->route('community.contributor.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }
}
