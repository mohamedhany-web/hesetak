@extends('layouts.admin')
@section('title', 'تسجيلات الجلسات')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white"><i class="fas fa-play-circle text-emerald-500 ml-2"></i>تسجيلات الجلسات</h1>
            <p class="text-sm text-slate-500 mt-1">Jibri → R2 ثم تسجيل هنا (ويب هوك أو إضافة يدوية)</p>
        </div>
        <a href="{{ route('admin.live-recordings.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold shadow-lg shadow-emerald-500/25 transition-all">
            <i class="fas fa-plus"></i> إضافة تسجيل (R2 / يدوي)
        </a>
    </div>

    <form method="GET" class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="text-xs text-slate-500 mb-1 block">بحث</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="اسم التسجيل أو الجلسة..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
        </div>
        @if(isset($sessions) && $sessions->isNotEmpty())
        <div>
            <label class="text-xs text-slate-500 mb-1 block">الجلسة</label>
            <select name="session_id" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <option value="">الكل</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ Str::limit($s->title, 35) }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <label class="text-xs text-slate-500 mb-1 block">الحالة</label>
            <select name="status" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <option value="">الكل</option>
                <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>جاهز</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>فشل</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-600 text-white rounded-lg text-sm font-medium hover:bg-slate-700 transition-colors"><i class="fas fa-search ml-1"></i> بحث</button>
    </form>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">#</th>
                    <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">التسجيل</th>
                    <th class="px-4 py-3 text-right text-slate-600 dark:text-slate-300 font-semibold">الجلسة</th>
                    <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">المدة</th>
                    <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">الحجم</th>
                    <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">الحالة</th>
                    <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">منشور</th>
                    <th class="px-4 py-3 text-center text-slate-600 dark:text-slate-300 font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($recordings as $rec)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 text-slate-500">{{ $rec->id }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800 dark:text-white">{{ $rec->title ?? 'تسجيل #' . $rec->id }}</td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ Str::limit($rec->session?->title ?? '—', 30) }}</td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $rec->duration_for_humans }}</td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $rec->file_size_for_humans }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($rec->status === 'ready')
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 text-xs">جاهز</span>
                        @elseif($rec->status === 'processing')
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 text-xs">معالجة</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-xs">فشل</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('admin.live-recordings.toggle-publish', $rec) }}" class="inline">
                            @csrf
                            <button class="text-lg {{ $rec->is_published ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600' }}" title="{{ $rec->is_published ? 'إلغاء النشر' : 'نشر' }}">
                                <i class="fas fa-toggle-{{ $rec->is_published ? 'on' : 'off' }}"></i>
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="inline-flex items-center gap-1">
                            @if($rec->getUrl())
                            <a href="{{ $rec->getUrl() }}" target="_blank" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-blue-500" title="مشاهدة"><i class="fas fa-external-link-alt text-xs"></i></a>
                            @endif
                            <form method="POST" action="{{ route('admin.live-recordings.destroy', $rec) }}" class="inline" onsubmit="return confirm('حذف هذا التسجيل؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 text-red-500" title="حذف">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500"><i class="fas fa-film text-4xl text-slate-300 mb-3 block"></i>لا توجد تسجيلات بعد</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($recordings->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">{{ $recordings->links() }}</div>
        @endif
    </div>
</div>
@endsection
