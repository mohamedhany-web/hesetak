@extends('layouts.admin')

@section('title', 'عرض رسالة التواصل - حصتك')
@section('page_title', 'عرض الرسالة')

@section('content')
@php
    $isRead = ! empty($contactMessage->read_at);
    $statusTone = $isRead ? 'bg-canvas-muted text-muted' : 'bg-metal/15 text-metal';
    $statusLabel = $isRead ? 'مقروءة' : 'غير مقروءة';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">رسائل التواصل · من صفحة «تواصل معنا»</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $contactMessage->subject ?: 'بدون موضوع' }}</h2>
            <p class="mt-1 text-sm text-muted">رسالة #{{ $contactMessage->id }} · {{ $contactMessage->created_at?->diffForHumans() }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.contact-messages.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
            @if($contactMessage->email)
                <a href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re: '.($contactMessage->subject ?: 'رسالتك')) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-reply text-xs"></i>
                    رد بالبريد
                </a>
            @endif
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-user text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">المرسل</p>
            <p class="mt-1 truncate text-sm font-semibold text-ink">{{ $contactMessage->name }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-envelope text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">البريد</p>
            <p class="mt-1 truncate text-sm font-semibold text-ink" title="{{ $contactMessage->email }}">{{ $contactMessage->email }}</p>
            @if($contactMessage->phone)
                <p class="mt-0.5 text-sm text-muted" dir="ltr">{{ $contactMessage->phone }}</p>
            @endif
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $statusTone }}">
                <i class="fas fa-{{ $isRead ? 'check-double' : 'envelope-open' }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الحالة</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $statusTone }}">{{ $statusLabel }}</span>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-calendar-day text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">تاريخ الإرسال</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $contactMessage->created_at?->format('Y-m-d H:i') }}</p>
            @if($contactMessage->read_at)
                <p class="mt-0.5 text-xs text-muted">قُرئت {{ $contactMessage->read_at->format('Y-m-d H:i') }}</p>
            @endif
        </article>
    </section>

    <div class="grid gap-5 lg:grid-cols-5">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-3">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">نص الرسالة</h3>
                <p class="mt-0.5 text-xs text-muted">الموضوع: {{ $contactMessage->subject ?: '—' }}</p>
            </div>
            <div class="p-4 sm:p-5">
                <div class="rounded-xl border border-line bg-canvas/60 p-4 sm:p-5">
                    <p class="whitespace-pre-line text-sm leading-7 text-ink">{{ $contactMessage->message }}</p>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-2">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات المرسل</h3>
                <p class="mt-0.5 text-xs text-muted">للتواصل السريع والمتابعة</p>
            </div>
            <div class="space-y-3 p-4 sm:p-5">
                <div class="rounded-xl border border-line bg-canvas/60 p-4">
                    <p class="text-xs font-medium text-muted">الاسم</p>
                    <p class="mt-1 text-sm font-semibold text-ink">{{ $contactMessage->name }}</p>
                </div>
                <div class="rounded-xl border border-line bg-canvas/60 p-4">
                    <p class="text-xs font-medium text-muted">البريد الإلكتروني</p>
                    <p class="mt-1 break-all text-sm font-semibold text-ink">{{ $contactMessage->email }}</p>
                </div>
                <div class="rounded-xl border border-line bg-canvas/60 p-4">
                    <p class="text-xs font-medium text-muted">الهاتف</p>
                    <p class="mt-1 text-sm font-semibold text-ink" dir="ltr">{{ $contactMessage->phone ?: '—' }}</p>
                </div>

                <div class="space-y-2 border-t border-line pt-4">
                    @if($isRead)
                        <form method="post" action="{{ route('admin.contact-messages.mark-as-unread', $contactMessage) }}">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                                <i class="fas fa-envelope text-xs"></i>
                                تعليم كغير مقروءة
                            </button>
                        </form>
                    @else
                        <form method="post" action="{{ route('admin.contact-messages.mark-as-read', $contactMessage) }}">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                                <i class="fas fa-check text-xs"></i>
                                تعليم كمقروءة
                            </button>
                        </form>
                    @endif

                    @if($contactMessage->email)
                        <a href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re: '.($contactMessage->subject ?: 'رسالتك')) }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-paper-plane text-xs"></i>
                            فتح البريد للرد
                        </a>
                    @endif

                    <form method="post" action="{{ route('admin.contact-messages.destroy', $contactMessage) }}" onsubmit="return confirm('حذف هذه الرسالة نهائياً؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-danger/30 bg-surface px-4 text-sm font-medium text-danger transition hover:bg-danger/5">
                            <i class="fas fa-trash text-xs"></i>
                            حذف الرسالة
                        </button>
                    </form>
                </div>
            </div>
        </article>
    </div>
</div>
@endsection
