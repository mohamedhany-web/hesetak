<?php

namespace App\Http\Middleware;

use App\Support\StudentExclusiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces a single active session for pure student accounts.
 * Instructors/admins/employees are unaffected (legacy soft cache behavior removed for students).
 */
class PreventConcurrentSessions
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        if (! StudentExclusiveSession::appliesTo($user)) {
            return $next($request);
        }

        $sessionId = Session::getId();

        if (StudentExclusiveSession::isCurrent($user, $sessionId)) {
            StudentExclusiveSession::refreshTtl($user, $sessionId);

            return $next($request);
        }

        Log::warning('Student concurrent session rejected', [
            'user_id' => $user->id,
            'current_session' => $sessionId,
            'url' => $request->fullUrl(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors([
                'email' => 'تم تسجيل الدخول من جهاز آخر. هذه الجلسة لم تعد صالحة.',
            ]);
    }
}
