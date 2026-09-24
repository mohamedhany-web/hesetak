@extends('layouts.admin')
@section('title', 'سيرفرات البث')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white"><i class="fas fa-server text-cyan-500 ml-2"></i>سيرفرات البث (VPS)</h1>
            <p class="text-sm text-slate-500 mt-1">إدارة سيرفرات البث — اختبار الاتصال والتحكم بالنطاق الافتراضي</p>
        </div>
        <a href="{{ route('admin.live-servers.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl font-semibold shadow-lg shadow-cyan-500/25 transition-all">
            <i class="fas fa-plus"></i> إضافة سيرفر
        </a>
    </div>

    @if($defaultLiveHost ?? null)
    <div class="bg-slate-100 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300">
        <i class="fas fa-link text-cyan-500 ml-1"></i>
        <strong>نطاق Hissatak Meeting الافتراضي:</strong> <code class="bg-slate-200 dark:bg-slate-600 px-1.5 py-0.5 rounded">{{ $defaultLiveHost }}</code>
    </div>
    @endif

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-emerald-700 dark:text-emerald-400 text-sm"><i class="fas fa-check-circle ml-1"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-red-700 dark:text-red-400 text-sm"><i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}</div>
    @endif

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($servers as $server)
        @php $isDefault = ($defaultLiveHost ?? '') && trim($defaultLiveHost) === trim($server->domain); $loadCount = $server->active_sessions_count; $loadPct = $server->max_participants > 0 ? min(100, (int) round(($loadCount / $server->max_participants) * 100)) : 0; @endphp
        <div class="bg-white dark:bg-slate-800 rounded-xl border {{ $isDefault ? 'border-cyan-500 ring-1 ring-cyan-500/30' : 'border-slate-200 dark:border-slate-700' }} p-5 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">{{ $server->name }}</h3>
                    <p class="text-sm text-slate-400 font-mono mt-0.5">{{ $server->domain }}</p>
                    @if($isDefault)<span class="inline-block mt-1 px-2 py-0.5 rounded bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400 text-xs font-medium">نطاق افتراضي</span>@endif
                </div>
                @if($server->status === 'active')
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 text-xs font-medium">نشط</span>
                @elseif($server->status === 'maintenance')
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 text-xs font-medium">صيانة</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-600 text-slate-500 text-xs font-medium">معطل</span>
                @endif
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">النوع:</span><span class="font-medium text-slate-700 dark:text-slate-300">{{ ucfirst($server->provider) }}</span></div>
                @if($server->ip_address)
                <div class="flex justify-between"><span class="text-slate-500">IP:</span><span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ $server->ip_address }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-slate-500">الجلسات النشطة:</span><span class="font-bold text-slate-700 dark:text-white">{{ $server->active_sessions_count }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">الحمل (فعلي):</span><span>{{ $loadCount }} / {{ $server->max_participants }}</span></div>
            </div>

            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all {{ $loadPct > 80 ? 'bg-red-500' : ($loadPct > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $loadPct }}%"></div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                <form method="POST" action="{{ route('admin.live-servers.test-connection', $server) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 rounded-lg text-xs font-medium hover:bg-cyan-100 dark:hover:bg-cyan-900/50"> <i class="fas fa-plug"></i> اختبار الاتصال</button></form>
                @if($server->status === 'active' && !$isDefault)
                <form method="POST" action="{{ route('admin.live-servers.set-default', $server) }}" class="inline">@csrf<button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-lg text-xs font-medium hover:bg-violet-100 dark:hover:bg-violet-900/50"> <i class="fas fa-star"></i> استخدام كنطاق افتراضي</button></form>
                @endif
                @if(!empty($server->config['ssh_username'] ?? null))
                <a href="{{ route('admin.live-servers.ssh-browse', $server) }}" class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-medium hover:bg-emerald-100">ملفات</a>
                @endif
                <a href="{{ route('admin.live-servers.edit', $server) }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-medium hover:bg-slate-200">تعديل</a>
                <form method="POST" action="{{ route('admin.live-servers.toggle-status', $server) }}" class="inline">@csrf<button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-medium hover:bg-slate-200">{{ $server->status === 'active' ? 'إيقاف' : 'تفعيل' }}</button></form>
                <form method="POST" action="{{ route('admin.live-servers.destroy', $server) }}" class="inline" onsubmit="return confirm('حذف السيرفر؟')">@csrf @method('DELETE')<button class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-lg text-xs font-medium hover:bg-red-100">حذف</button></form>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 text-center py-12">
            <i class="fas fa-server text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
            <p class="text-slate-500">لا توجد سيرفرات بث بعد</p>
            <a href="{{ route('admin.live-servers.create') }}" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-cyan-500 text-white rounded-lg text-sm font-medium hover:bg-cyan-600 transition-colors"><i class="fas fa-plus"></i> أضف أول سيرفر</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
