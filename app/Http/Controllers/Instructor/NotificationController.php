<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $base = Auth::user()->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'instructor');
            });

        $stats = [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->where('is_read', false)->count(),
        ];
        $stats['read'] = max(0, $stats['total'] - $stats['unread']);

        $query = (clone $base)->with(['sender'])->latest();

        if ($request->filled('status') && $request->status === 'unread') {
            $query->where('is_read', false);
        } elseif ($request->filled('status') && $request->status === 'read') {
            $query->where('is_read', true);
        }

        $notifications = $query->paginate(25)->withQueryString();

        return view('instructor.notifications.index', compact('notifications', 'stats'));
    }

    public function go(Notification $notification): RedirectResponse
    {
        $this->authorizeOwned($notification);
        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        $url = $notification->action_url ?: route('instructor.notifications.index');

        return redirect()->to($url);
    }

    public function markAsRead(Notification $notification): RedirectResponse
    {
        $this->authorizeOwned($notification);
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function unreadCount(): JsonResponse
    {
        $base = Auth::user()->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'instructor');
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        $count = (clone $base)->where('is_read', false)->count();
        $items = (clone $base)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Notification $n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => \Illuminate\Support\Str::limit((string) $n->message, 110),
                    'is_read' => (bool) $n->is_read,
                    'href' => $n->action_url
                        ? route('instructor.notifications.go', $n)
                        : route('instructor.notifications.index'),
                    'time' => optional($n->created_at)->diffForHumans(),
                    'icon' => $n->type_icon,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'count' => $count,
            'unread_count' => $count,
            'items' => $items,
            'email' => Auth::user()->email,
        ]);
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        Auth::user()->customNotifications()
            ->where(function ($q) {
                $q->whereNull('audience')->orWhere('audience', 'instructor');
            })
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    private function authorizeOwned(Notification $notification): void
    {
        abort_unless((int) $notification->user_id === (int) Auth::id(), 403);
    }
}
