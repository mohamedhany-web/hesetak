<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AdminUsersDirectory;
use App\Support\UserContactPrivacy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersDirectoryController extends Controller
{
    public function __construct(
        protected AdminUsersDirectory $directory,
    ) {}

    public function index(Request $request): View
    {
        $viewer = $request->user();
        $data = $this->directory->index($request, $viewer);

        return view('admin.users.index', [
            'users' => $data['users'],
            'counts' => $data['counts'],
            'type' => $data['type'],
            'canViewContacts' => $data['can_view_contacts'],
            'filters' => $data['filters'],
            'directory' => $this->directory,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $viewer = $request->user();
        $this->assertCanManageUsers($viewer);

        $key = 'users_directory_export_'.$viewer->id;
        if (RateLimiter::tooManyAttempts($key, 8)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', "تم تجاوز حد التصدير. أعد المحاولة بعد {$seconds} ثانية.");
        }
        RateLimiter::hit($key, 300);

        $type = $this->directory->normalizeType($request->input('type'));
        $canViewContacts = UserContactPrivacy::canViewContacts($viewer);
        $rows = $this->directory->exportRows($request, $viewer);

        ActivityLog::logActivity(
            'users.export',
            null,
            null,
            [
                'type' => $type,
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'rows' => $rows->count(),
                'contacts_visible' => $canViewContacts,
            ],
            'تصدير دليل المستخدمين (CSV)'
        );

        $filename = 'hesetak-users-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'الاسم',
                'البريد',
                'الجوال',
                'النوع',
                'الحالة',
                'تاريخ التسجيل',
                'آخر دخول',
                'تفاصيل النوع',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['email'],
                    $row['phone'],
                    $row['type_label'],
                    $row['status_label'],
                    $row['registered_at'],
                    $row['last_login_at'] === '—' ? '' : $row['last_login_at'],
                    $row['type_detail'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertCanManageUsers($actor);

        if ($actor->id === $user->id) {
            return back()->with('error', 'لا يمكن إيقاف حسابك الحالي من هنا.');
        }

        if ((string) $user->role === 'super_admin' && (string) $actor->role !== 'super_admin') {
            return back()->with('error', 'لا يمكن إيقاف حساب المدير العام.');
        }

        $wasActive = (bool) $user->is_active;
        $user->forceFill(['is_active' => ! $wasActive])->save();

        ActivityLog::logActivity(
            $user->is_active ? 'users.activate' : 'users.suspend',
            $user,
            ['is_active' => $wasActive],
            ['is_active' => $user->is_active],
            ($user->is_active ? 'إعادة تفعيل' : 'إيقاف').' مستخدم: '.$user->name
        );

        return back()->with('success', $user->is_active
            ? 'تم تفعيل حساب «'.$user->name.'».'
            : 'تم إيقاف حساب «'.$user->name.'».');
    }

    public function resendCredentials(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertCanManageUsers($actor);

        $email = trim((string) $user->email);
        if ($email === '') {
            return back()->with('error', 'لا يوجد بريد إلكتروني لإرسال بيانات الدخول.');
        }

        $key = 'users_resend_credentials_'.$actor->id.'_'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', "تم تجاوز حد إعادة الإرسال لهذا الحساب. أعد المحاولة بعد {$seconds} ثانية.");
        }
        RateLimiter::hit($key, 600);

        $status = Password::broker()->sendResetLink(['email' => $email]);

        ActivityLog::logActivity(
            'users.resend_credentials',
            $user,
            null,
            ['email_masked' => UserContactPrivacy::maskEmail($email), 'status' => $status],
            'إعادة إرسال رابط الدخول/استعادة كلمة المرور: '.$user->name
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريد المستخدم.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('error', 'تم طلب رابط مؤخراً لهذا البريد. انتظر قليلاً ثم أعد المحاولة.');
        }

        return back()->with('error', 'تعذّر إرسال رابط الدخول. تحقق من إعدادات البريد.');
    }

    protected function assertCanManageUsers(?User $actor): void
    {
        if (! $actor) {
            abort(403);
        }

        if (in_array((string) $actor->role, ['admin', 'super_admin'], true)) {
            return;
        }

        if ($actor->hasPermission('manage.users')) {
            return;
        }

        abort(403);
    }
}
