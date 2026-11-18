<?php

namespace App\Http\Controllers\Student;

use App\Helpers\VideoHelper;
use App\Http\Controllers\Controller;
use App\Models\LiveRecording;
use App\Models\LiveSession;
use App\Services\LibraryFolderAccessService;
use Illuminate\Http\Request;

class LiveRecordingController extends Controller
{
    /**
     * قائمة التسجيلات المنشورة للجلسات التي يمكن للطالب الوصول إليها.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $sessionIds = LiveSession::query()
            ->where('status', 'ended')
            ->whereHas('recordings', fn ($q) => $q->where('status', 'ready')->where('is_published', true))
            ->with('course')
            ->get()
            ->filter(fn (LiveSession $session) => $session->canUserJoin($user))
            ->pluck('id');

        $recordings = LiveRecording::query()
            ->with(['session.course', 'session.instructor', 'folder'])
            ->whereIn('session_id', $sessionIds)
            ->where('status', 'ready')
            ->where('is_published', true)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // نفس شرط صفحة المشاهدة: مجلد المكتبة المرتبط يتطلب اشتراكاً.
        $recordings->setCollection(
            $recordings->getCollection()->filter(
                fn (LiveRecording $recording) => ! $recording->folder
                    || LibraryFolderAccessService::canAccessFolder($user, $recording->folder)
            )->values()
        );

        return view('student.live-recordings.index', compact('recordings'));
    }

    /**
     * مشاهدة تسجيل داخل المنصة (مشغّل مضمّن).
     */
    public function show(LiveRecording $liveRecording)
    {
        $liveRecording->load(['session.course', 'session.instructor', 'folder']);
        $session = $liveRecording->session;
        $user = auth()->user();

        if (! $session || ! $session->canUserJoin($user)) {
            abort(403, 'ليس لديك صلاحية مشاهدة هذا التسجيل');
        }
        if ($liveRecording->status !== 'ready' || ! $liveRecording->is_published) {
            abort(404);
        }

        if ($liveRecording->folder) {
            abort_unless(
                LibraryFolderAccessService::canAccessFolder($user, $liveRecording->folder),
                403,
                'يلزم اشتراك باقة المكتبات لهذه السنة لمشاهدة هذا التسجيل.'
            );
        }

        $url = $liveRecording->getUrl();
        if (! $url) {
            abort(404, 'رابط التسجيل غير متوفر حالياً');
        }

        $embedUrl = VideoHelper::getEmbedUrl($url);
        $directUrl = $embedUrl ? null : (VideoHelper::getDirectVideoUrl($url) ?: $url);
        $source = VideoHelper::getVideoSource($url);
        $thumbnail = VideoHelper::getThumbnail($url);

        return view('student.live-recordings.show', [
            'liveRecording' => $liveRecording,
            'url' => $url,
            'embedUrl' => $embedUrl,
            'directUrl' => $directUrl,
            'source' => $source,
            'thumbnail' => $thumbnail,
        ]);
    }
}
