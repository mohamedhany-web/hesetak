@extends('layouts.admin')

@section('title', 'رسائل التواصل - حصتك')
@section('page_title', 'رسائل التواصل')

@section('content')
@php
    $kpis = [
        ['label' => 'إجمالي الرسائل', 'value' => $stats['total'], 'icon' => 'fa-inbox', 'tone' => 'accent', 'note' => 'جميع الرسائل'],
        ['label' => 'غير مقروءة', 'value' => $stats['unread'], 'icon' => 'fa-envelope', 'tone' => 'metal', 'note' => 'تحتاج للمراجعة'],
        ['label' => 'مقروءة', 'value' => $stats['read'], 'icon' => 'fa-check-double', 'tone' => 'muted', 'note' => 'تمت المراجعة'],
        ['label' => 'رسائل اليوم', 'value' => $stats['today'], 'icon' => 'fa-calendar-day', 'tone' => 'accent', 'note' => 'تم الاستلام اليوم'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">رسائل الزوار من صفحة «تواصل معنا»</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">رسائل التواصل</h2>
        </div>
        @if($stats['unread'] > 0)
            <span class="inline-flex h-9 items-center gap-2 rounded-xl bg-metal/15 px-4 text-sm font-medium text-metal">
                <i class="fas fa-exclamation-circle text-xs"></i>
                {{ number_format($stats['unread']) }} رسالة غير مقروءة
            </span>
        @endif
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالاسم أو البريد أو الموضوع، أو صفِّ حسب الحالة</p>
        </div>
        <form method="GET" action="{{ route('admin.contact-messages.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="text" name="search" value="{{ request('search') }}"
                       placeholder="الاسم، البريد الإلكتروني، أو الموضوع..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">جميع الرسائل</option>
                    <option value="unread" @selected(request('status') === 'unread')>غير مقروءة</option>
                    <option value="read" @selected(request('status') === 'read')>مقروءة</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.contact-messages.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">سجل الرسائل</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($messages->total()) }} رسالة</p>
            </div>
            <span class="text-xs text-muted">آخر تحديث: {{ now()->format('H:i') }}</span>
        </div>

        @if($messages->count() > 0)
            <div class="admin-table-wrap">
                <table class="w-full min-w-[900px] text-right text-sm">
                    <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">المرسل</th>
                            <th class="px-3 py-3 font-medium">الموضوع</th>
                            <th class="px-3 py-3 font-medium">الحالة</th>
                            <th class="px-3 py-3 font-medium">تاريخ الإرسال</th>
                            <th class="px-5 py-3 font-medium">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($messages as $message)
                            <tr class="transition hover:bg-[#f7f8fa] {{ ! $message->read_at ? 'bg-accent-soft/30' : '' }}">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">
                                            {{ mb_substr($message->name, 0, 1, 'UTF-8') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink">{{ $message->name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-muted"><i class="fas fa-envelope ml-1 text-[10px]"></i>{{ $message->email }}</p>
                                            @if($message->phone)
                                                <p class="mt-0.5 truncate text-xs text-muted"><i class="fas fa-phone ml-1 text-[10px]"></i>{{ $message->phone }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-ink">{{ $message->subject }}</p>
                                    <p class="mt-1 line-clamp-2 max-w-md text-xs leading-5 text-muted">{{ Str::limit($message->message, 100) }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    @if($message->read_at)
                                        <span class="rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">مقروءة</span>
                                    @else
                                        <span class="rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">غير مقروءة</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <p class="font-medium tabular-nums text-ink">{{ $message->created_at->format('d/m/Y') }}</p>
                                    <p class="mt-0.5 text-xs tabular-nums text-muted">{{ $message->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.contact-messages.show', $message) }}"
                                           class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                           title="عرض التفاصيل">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        @if($message->read_at)
                                            <form action="{{ route('admin.contact-messages.mark-as-unread', $message) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-metal/15 text-metal transition hover:bg-metal hover:text-white"
                                                        title="تحديد كغير مقروءة">
                                                    <i class="fas fa-envelope text-xs"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.contact-messages.mark-as-read', $message) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-canvas-muted text-muted transition hover:bg-ink hover:text-white"
                                                        title="تحديد كمقروءة">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.contact-messages.destroy', $message) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger transition hover:bg-danger hover:text-white"
                                                    title="حذف"
                                                    onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($messages->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $messages->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="px-4 py-16 text-center sm:px-5">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-inbox"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا توجد رسائل</p>
                <p class="mt-1 text-xs text-muted">
                    @if(request()->anyFilled(['search', 'status']))
                        لا توجد نتائج مطابقة للفلتر الحالي.
                    @else
                        لم يتم استلام أي رسائل تواصل بعد.
                    @endif
                </p>
            </div>
        @endif
    </article>
</div>
@endsection
