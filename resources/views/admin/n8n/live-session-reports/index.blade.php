@extends('layouts.admin')

@section('title', 'تقارير البث الذكي (n8n)')

@section('content')
    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-md">
                    <i class="fas fa-robot text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                        تقارير البث الذكي (n8n)
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        مراجعة التقارير التي تم إنشاؤها تلقائياً من جلسات البث المباشر عبر n8n وربطها بالمعلمين والجلسات.
                    </p>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-slate-500 mb-1 block">الحالة</label>
                <select name="status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="">كل الحالات</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>فشل</option>
                </select>
            </div>
            <div class="w-full sm:w-48">
                <label class="text-xs text-slate-500 mb-1 block">معرّف المعلم</label>
                <input type="number"
                       name="instructor_id"
                       value="{{ $instructorId }}"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                       placeholder="ID المعلم">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm">
                    <i class="fas fa-filter ml-1"></i>
                    <span>تطبيق الفلاتر</span>
                </button>
                @if(request()->hasAny(['status', 'instructor_id']))
                    <a href="{{ route('admin.n8n.live-session-reports.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-sm hover:bg-slate-300 dark:hover:bg-slate-600">
                        مسح
                    </a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">#</th>
                            <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">الجلسة</th>
                            <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">المعلم</th>
                            <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">الحالة</th>
                            <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">العنوان / الملخص</th>
                            <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">التسجيل</th>
                            <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">آخر تحديث</th>
                            <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($reports as $report)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3 text-slate-500">{{ $report->id }}</td>
                            <td class="px-4 py-3">
                                @if($report->session)
                                    <div>
                                        <div class="font-semibold text-slate-800 dark:text-white">
                                            {{ \Illuminate\Support\Str::limit($report->session->title, 40) }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            ID: {{ $report->session->id }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($report->instructor)
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-white">
                                            {{ $report->instructor->name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            ID: {{ $report->instructor->id }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $badgeClasses = match($report->status) {
                                        'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200',
                                        'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                        default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClasses }}">
                                    @if($report->status === 'processing')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    @elseif($report->status === 'completed')
                                        <i class="fas fa-check-circle text-[11px]"></i>
                                    @elseif($report->status === 'failed')
                                        <i class="fas fa-times-circle text-[11px]"></i>
                                    @endif
                                    <span>{{ $report->status }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="space-y-1">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100 truncate">
                                        {{ $report->title ?? '—' }}
                                    </div>
                                    @if($report->summary)
                                        <div class="text-xs text-slate-500 dark:text-slate-300 line-clamp-2" title="{{ $report->summary }}">
                                            {{ \Illuminate\Support\Str::limit($report->summary, 120) }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($report->recording)
                                    <a href="{{ $report->recording->getUrl() }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-emerald-500/60 text-emerald-600 dark:text-emerald-300 text-xs font-medium hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
                                        <i class="fas fa-play text-[11px]"></i>
                                        تشغيل
                                    </a>
                                @elseif($report->audio_path)
                                    <span class="text-[11px] font-mono text-slate-500 break-all">
                                        {{ $report->audio_path }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                {{ $report->updated_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-center gap-1">
                                    <form method="POST" action="{{ route('admin.n8n.live-session-reports.retry', $report) }}">@csrf
                                        <button type="submit" class="px-2 py-1 text-[11px] rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50" title="إعادة المحاولة">إعادة</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.n8n.live-session-reports.mark-completed', $report) }}">@csrf
                                        <button type="submit" class="px-2 py-1 text-[11px] rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50" title="تعليم كمكتمل">مكتمل</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.n8n.live-session-reports.mark-failed', $report) }}" onsubmit="return confirm('تعليم التقرير كفاشل؟');">@csrf
                                        <button type="submit" class="px-2 py-1 text-[11px] rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50" title="تعليم كفاشل">فشل</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <i class="fas fa-robot text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
                                <p class="text-slate-500 dark:text-slate-300">لا توجد تقارير حالياً.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

