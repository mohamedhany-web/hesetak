<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * عرض الإشعارات للطالب
     */
    public function index(Request $request)
    {
        $viewer = \App\Support\ActiveChildContext::resolve(Auth::user()) ?? Auth::user();
        $query = $viewer->customNotifications()->with(['sender'])
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'student');
            });

        // فلترة حسب النوع
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->status === 'unread') {
                $query->where('is_read', false);
            }
        }

        // فلترة حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // بحث بسيط
        if ($request->filled('q')) {
            $needle = trim((string) $request->q);
            if ($needle !== '') {
                $query->where(function ($inner) use ($needle) {
                    $inner->where('title', 'like', '%'.$needle.'%')
                        ->orWhere('message', 'like', '%'.$needle.'%');
                });
            }
        }

        $notifications = $query->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('is_read', 'asc')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // إحصائيات (إشعارات الطالب فقط: audience null أو student) — لولي الأمر: إشعارات الابن
        $baseStudentNotifications = $viewer->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'student');
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        $stats = [
            'total' => (clone $baseStudentNotifications)->count(),
            'unread' => (clone $baseStudentNotifications)->unread()->count(),
            'today' => (clone $baseStudentNotifications)->whereDate('created_at', today())->count(),
            'urgent' => (clone $baseStudentNotifications)->where('priority', 'urgent')->unread()->count(),
        ];

        $notificationTypes = Notification::getTypes();
        $priorities = Notification::getPriorities();

        return view('student.notifications.index', compact('notifications', 'stats', 'notificationTypes', 'priorities'));
    }

    /**
     * عرض تفاصيل الإشعار
     */
    public function show(Notification $notification)
    {
        // التحقق من الصلاحية والمستهدف (عرض إشعارات الطالب فقط)
        if ($notification->user_id !== Auth::id()) {
            return redirect()->route('notifications')->with('error', 'غير مصرح لك بعرض هذا الإشعار');
        }
        if ($notification->audience !== null && $notification->audience !== 'student') {
            return redirect()->route('notifications')->with('error', 'هذا الإشعار غير موجّه للطلاب');
        }

        // تحديد كمقروء
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        $notification->load(['sender']);

        $otherNotifications = Auth::user()->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'student');
            })
            ->where('id', '!=', $notification->id)
            ->valid()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('student.notifications.show', compact('notification', 'otherNotifications'));
    }

    /**
     * انتقال آمن لرابط الإشعار (منع تداخل الصلاحيات)
     */
    public function go(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return redirect()->route('notifications')->with('error', 'غير مصرح');
        }
        if ($notification->audience !== null && $notification->audience !== 'student') {
            return redirect()->route('notifications')->with('error', 'هذا الإشعار غير موجّه للطلاب');
        }
        if (empty($notification->action_url)) {
            return redirect()->route('notifications');
        }
        $url = $notification->action_url;
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        $host = $parsed['host'] ?? null;
        $appUrl = parse_url(config('app.url'));
        $appHost = $appUrl['host'] ?? null;
        if ($host && $host !== $appHost) {
            return redirect()->route('notifications')->with('error', 'رابط غير مسموح');
        }
        if (preg_match('#^/(employee|admin)(/|$)#', $path)) {
            return redirect()->route('notifications')->with('error', 'رابط غير مسموح للطالب');
        }
        return redirect()->to($url);
    }

    /**
     * تحديد الإشعار كمقروء
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead()
    {
        $count = Auth::user()
                    ->customNotifications()
                    ->where(function ($q) {
                        $q->whereNull('audience')->orWhere('audience', 'student');
                    })
                    ->unread()
                    ->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);

        return response()->json([
            'success' => true,
            'message' => "تم تحديد {$count} إشعار كمقروء",
            'count' => $count,
        ]);
    }

    /**
     * حذف الإشعار
     */
    public function destroy(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }

    /**
     * الحصول على عدد الإشعارات غير المقروءة
     */
    public function getUnreadCount()
    {
        $count = Auth::user()->customNotifications()->unread()->where(function($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->count();
        
        return response()->json(['count' => $count]);
    }

    /**
     * الحصول على آخر الإشعارات
     */
    public function getRecent()
    {
        $notifications = Auth::user()
                           ->customNotifications()
                           ->with(['sender'])
                           ->where(function ($q) {
                               $q->whereNull('audience')->orWhere('audience', 'student');
                           })
                           ->where(function($q) {
                               $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                           })
                           ->orderBy('created_at', 'desc')
                           ->take(5)
                           ->get();

        return response()->json($notifications);
    }

    /**
     * بيانات جرس النافبار (تحديث تلقائي) — JSON.
     */
    public function navPoll()
    {
        $base = Auth::user()->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'student');
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        $count = (clone $base)->unread()->count();

        $items = (clone $base)
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Notification $n) {
                $href = route('notifications.go', $n);
                if (empty($n->action_url)) {
                    $href = route('notifications.show', $n);
                }

                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => \Illuminate\Support\Str::limit((string) $n->message, 110),
                    'priority' => $n->priority,
                    'is_read' => (bool) $n->is_read,
                    'href' => $href,
                    'time' => optional($n->created_at)->diffForHumans(),
                    'icon' => $n->type_icon,
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $count,
            'items' => $items,
            'email' => Auth::user()->email,
        ]);
    }

    /**
     * حذف الإشعارات القديمة والمقروءة
     */
    public function cleanup()
    {
        $count = Auth::user()
                    ->customNotifications()
                    ->where('is_read', true)
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف {$count} إشعار قديم",
            'count' => $count,
        ]);
    }
}