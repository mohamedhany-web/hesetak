@extends(!empty($useInstructorRoutes) ? 'layouts.app' : 'layouts.student-timeline')

@section('title', 'فصول حصتك — إدارة الاجتماعات')
@section('header', 'إدارة اجتماعات Classroom')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-lg p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white">فصول حصتك</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">صفحة منظمة لإدارة كل اجتماعاتك وصلاحياتها وإعداداتها.</p>
            </div>
            <a href="{{ route('student.classroom.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold shadow-lg shadow-red-500/30">
                <i class="fas fa-plus"></i>
                إنشاء اجتماع جديد
            </a>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-800 dark:text-white mb-3">أدوات الاجتماع</h2>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('student.classroom.whiteboard') }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-800 dark:text-amber-200 text-sm font-semibold border border-amber-400/40 dark:border-amber-500/35 transition-colors">
                وايت بورد
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">إجمالي الاجتماعات</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">اجتماعات مباشرة</p>
            <p class="text-xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($stats['live']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">الحد الشهري / المستخدم</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($usedMeetingsThisMonth) }} / {{ number_format($limits['classroom_meetings_per_month']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">المتبقي هذا الشهر</p>
            <p class="text-xl font-bold {{ $remainingMeetingsThisMonth > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ number_format($remainingMeetingsThisMonth) }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
            <form method="GET" action="{{ route('student.classroom.index') }}" class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">فلتر الحالة:</span>
                @foreach(['all' => 'الكل', 'live' => 'مباشر', 'scheduled' => 'مجدول', 'ended' => 'منتهي'] as $k => $label)
                    <button type="submit" name="status" value="{{ $k }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $status === $k ? 'bg-sky-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/70">
                    <tr class="text-xs text-slate-600 dark:text-slate-300 uppercase">
                        <th class="px-4 py-3 text-right">الاجتماع</th>
                        <th class="px-4 py-3 text-right">الكود</th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">الحد/الذروة</th>
                        <th class="px-4 py-3 text-right">الرابط</th>
                        <th class="px-4 py-3 text-right">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($meetings as $m)
                        @php
                            $guestOk = \App\Services\ClassroomMeetingAccessService::allowsGuestJoin($m);
                            $joinUrl = $guestOk ? ($joinBaseUrl . '/' . $m->code) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/20">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $m->title ?: 'اجتماع بدون عنوان' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    الإنشاء: {{ $m->created_at->format('Y-m-d H:i') }}
                                    @if($m->scheduled_for)
                                        · الموعد: {{ $m->scheduled_for->format('Y-m-d H:i') }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300">
                                {{ $guestOk ? $m->code : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($m->isLive())
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-semibold bg-rose-100 text-rose-700">مباشر</span>
                                @elseif(!$m->started_at)
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-semibold bg-amber-100 text-amber-700">مجدول</span>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">منتهي</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                {{ (int) ($m->max_participants ?? 25) }} / {{ (int) ($m->participants_peak ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($guestOk && $joinUrl)
                                    <button type="button" onclick="navigator.clipboard.writeText(@json($joinUrl)); this.textContent='تم النسخ'; setTimeout(()=>this.textContent='نسخ', 1000)" class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold">نسخ</button>
                                @else
                                    <span class="text-xs text-emerald-700 dark:text-emerald-300 font-semibold">محمي</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('student.classroom.show', $m) }}" class="text-sky-600 hover:underline">عرض</a>
                                    <a href="{{ route('student.classroom.edit', $m) }}" class="text-amber-600 hover:underline">تعديل</a>
                                    @if(!$m->started_at && !$m->ended_at)
                                        <form action="{{ route('student.classroom.start-meeting', $m) }}" method="POST" class="inline">@csrf<button class="text-emerald-600 hover:underline">بدء</button></form>
                                    @elseif($m->isLive())
                                        <a href="{{ route('student.classroom.room', $m) }}" class="text-rose-600 hover:underline">دخول</a>
                                    @elseif($m->ended_at && $m->recording_download_url)
                                        <a href="{{ $m->recording_download_url }}" target="_blank" class="text-indigo-600 hover:underline">تحميل التسجيل</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">لا توجد اجتماعات حتى الآن.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $meetings->links() }}</div>
    </div>
</div>
@endsection
