@extends('layouts.admin')

@section('title', $liveSession->title.' - حصتك')
@section('page_title', 'تفاصيل جلسة البث')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.live-sessions.index') }}" class="hover:text-accent">جلسات البث</a>
                <span class="mx-1 text-line">/</span>
                تفاصيل
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $liveSession->title }}</h2>
            <p class="mt-1 font-mono text-xs text-muted">{{ $liveSession->room_name }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if($liveSession->status === 'live')
                <a href="{{ route('admin.live-sessions.room', $liveSession) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-door-open text-xs"></i> دخول الغرفة
                </a>
                <form method="POST" action="{{ route('admin.live-sessions.end', $liveSession) }}" onsubmit="return confirm('إنهاء البث؟')">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-danger/40 px-4 text-sm font-medium text-danger hover:bg-danger/10">
                        <i class="fas fa-stop text-xs"></i> إنهاء البث
                    </button>
                </form>
            @elseif($liveSession->status === 'scheduled')
                <form method="POST" action="{{ route('admin.live-sessions.start', $liveSession) }}">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                        <i class="fas fa-play text-xs"></i> بدء البث والدخول
                    </button>
                </form>
                <a href="{{ route('admin.live-sessions.edit', $liveSession) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink-soft hover:bg-canvas">
                    <i class="fas fa-edit text-xs"></i> تعديل
                </a>
                <form method="POST" action="{{ route('admin.live-sessions.cancel', $liveSession) }}" onsubmit="return confirm('إلغاء الجلسة؟')">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-muted hover:bg-canvas">إلغاء</button>
                </form>
            @endif
            <a href="{{ route('admin.live-sessions.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink-soft hover:bg-canvas">رجوع</a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error') || session('info'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink-soft shadow-soft">{{ session('error') ?: session('info') }}</div>
    @endif

    @if($liveSession->status === 'live')
        <div class="flex items-center gap-3 rounded-2xl border border-accent/30 bg-accent-soft/40 px-4 py-3 text-sm font-semibold text-accent">
            <span class="size-2.5 rounded-full bg-accent animate-pulse"></span>
            البث مباشر الآن — بدأ {{ $liveSession->started_at?->diffForHumans() }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">تفاصيل الجلسة</h3>
                </div>
                <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 text-sm">
                    <div>
                        <p class="text-xs text-muted">المضيف</p>
                        <p class="mt-1 font-semibold text-ink">
                            @if($liveSession->instructor)
                                <a href="{{ route('admin.users.show', $liveSession->instructor->id) }}" class="hover:text-accent">{{ $liveSession->instructor->name }}</a>
                            @else — @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">الكورس</p>
                        <p class="mt-1 font-semibold text-ink">{{ $liveSession->course?->title ?? 'جلسة عامة' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">الموعد</p>
                        <p class="mt-1 font-semibold text-ink">@if($liveSession->scheduled_at)<x-app-datetime :at="$liveSession->scheduled_at" pattern="Y/m/d H:i" />@else — @endif</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">المدة</p>
                        <p class="mt-1 font-semibold text-ink">{{ $liveSession->duration_for_humans }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">الحد الأقصى</p>
                        <p class="mt-1 font-semibold text-ink">{{ $liveSession->max_participants }} مشارك</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">السيرفر</p>
                        <p class="mt-1 font-semibold text-ink">{{ $liveSession->server?->name ?? 'افتراضي' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">تسجيل / شات</p>
                        <p class="mt-1 font-semibold text-ink">{{ $liveSession->is_recorded ? 'تسجيل مفعّل' : 'بدون تسجيل' }} · {{ $liveSession->allow_chat ? 'شات مفعّل' : 'شات معطّل' }}</p>
                    </div>
                    @if($liveSession->description)
                        <div class="sm:col-span-2 border-t border-line pt-4">
                            <p class="text-xs text-muted">الوصف</p>
                            <p class="mt-1 text-ink-soft">{{ $liveSession->description }}</p>
                        </div>
                    @endif
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">سجل الحضور <span class="text-sm font-normal text-muted">({{ $attendees->count() }})</span></h3>
                </div>
                @if($attendees->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b border-line bg-canvas/60 text-xs text-muted">
                                    <th class="px-4 py-3 text-start font-medium">المستخدم</th>
                                    <th class="px-4 py-3 text-center font-medium">الدور</th>
                                    <th class="px-4 py-3 text-start font-medium">الدخول</th>
                                    <th class="px-4 py-3 text-start font-medium">الخروج</th>
                                    <th class="px-4 py-3 text-center font-medium">المدة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($attendees as $att)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-ink">{{ $att->user?->name ?? 'محذوف' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if($att->role_in_session === 'instructor')
                                                <span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs text-accent">مضيف</span>
                                            @else
                                                <span class="rounded-full bg-canvas-muted px-2 py-0.5 text-xs text-muted">مشارك</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-muted">{{ $att->joined_at?->format('H:i:s') }}</td>
                                        <td class="px-4 py-3 text-xs text-muted">{{ $att->left_at?->format('H:i:s') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center text-xs text-muted">{{ $att->duration_for_humans }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-4 py-10 text-center text-sm text-muted">لا يوجد حضور بعد</p>
                @endif
            </article>
        </div>

        <div class="space-y-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">الحالة</h3>
                <div class="mt-3 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-muted">الحالة</span>
                        @if($liveSession->status === 'live')
                            <span class="inline-flex items-center gap-1 rounded-full bg-accent-soft px-2.5 py-1 text-xs font-bold text-accent"><span class="size-1.5 rounded-full bg-accent animate-pulse"></span> مباشر</span>
                        @elseif($liveSession->status === 'scheduled')
                            <span class="rounded-full bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">مجدولة</span>
                        @elseif($liveSession->status === 'ended')
                            <span class="rounded-full bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">منتهية</span>
                        @else
                            <span class="rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">ملغاة</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between"><span class="text-muted">الحضور</span><span class="font-semibold tabular-nums text-ink">{{ $attendees->count() }}</span></div>
                    @if($liveSession->started_at)
                        <div class="flex items-center justify-between"><span class="text-muted">بدأت</span><span class="text-xs text-ink-soft">{{ $liveSession->started_at->format('Y/m/d H:i') }}</span></div>
                    @endif
                    @if($liveSession->ended_at)
                        <div class="flex items-center justify-between"><span class="text-muted">انتهت</span><span class="text-xs text-ink-soft">{{ $liveSession->ended_at->format('Y/m/d H:i') }}</span></div>
                    @endif
                </div>
            </article>

            @if($liveSession->instructor)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <h3 class="text-sm font-semibold text-ink">المضيف</h3>
                    <p class="mt-2 text-sm font-medium text-ink">{{ $liveSession->instructor->name }}</p>
                    <a href="{{ route('admin.users.show', $liveSession->instructor->id) }}" class="btn-press mt-3 inline-flex h-9 w-full items-center justify-center rounded-xl border border-line text-sm font-medium text-ink-soft hover:border-accent/30 hover:text-accent">عرض الحساب</a>
                </article>
            @endif

            @if($liveSession->recordings->count() > 0)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <h3 class="text-sm font-semibold text-ink">التسجيلات</h3>
                    <div class="mt-3 space-y-2">
                        @foreach($liveSession->recordings as $rec)
                            <div class="flex items-center justify-between rounded-xl border border-line bg-canvas/40 px-3 py-2">
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ $rec->title ?? 'تسجيل' }}</p>
                                    <p class="text-[11px] text-muted">{{ $rec->duration_for_humans }} · {{ $rec->file_size_for_humans }}</p>
                                </div>
                                @if($rec->getUrl())
                                    <a href="{{ $rec->getUrl() }}" target="_blank" class="text-accent"><i class="fas fa-external-link-alt text-xs"></i></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif

            @if($liveSession->status !== 'live')
                <form method="POST" action="{{ route('admin.live-sessions.destroy', $liveSession) }}" onsubmit="return confirm('حذف الجلسة وجميع بيانات الحضور؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-danger/30 text-sm font-medium text-danger hover:bg-danger/10">
                        <i class="fas fa-trash-alt text-xs"></i> حذف الجلسة
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
