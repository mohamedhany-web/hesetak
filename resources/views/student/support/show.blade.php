@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_support'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $status = (string) ($ticket->status ?? 'open');
    $statusLabel = match ($status) {
        'open' => $isRtl ? 'مفتوحة' : 'Open',
        'in_progress', 'pending' => $isRtl ? 'قيد المعالجة' : 'In progress',
        'resolved' => $isRtl ? 'تم الحل' : 'Resolved',
        'closed' => $isRtl ? 'مغلقة' : 'Closed',
        default => $status,
    };
    $steps = [
        ['key' => 'open', 'label' => $isRtl ? 'استلام الرسالة' : 'Received'],
        ['key' => 'in_progress', 'label' => $isRtl ? 'قيد المعالجة' : 'In progress'],
        ['key' => 'resolved', 'label' => $isRtl ? 'تم الرد/الحل' : 'Resolved'],
        ['key' => 'closed', 'label' => $isRtl ? 'مغلقة' : 'Closed'],
    ];
    $rank = match ($status) {
        'open' => 0,
        'in_progress', 'pending' => 1,
        'resolved' => 2,
        'closed' => 3,
        default => 0,
    };
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $isRtl ? 'تتبع تذكرة الدعم' : 'Support ticket status',
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_support'), 'url' => route('student.support.index')],
        ['label' => \Illuminate\Support\Str::limit($ticket->subject, 40), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ $ticket->subject }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ $isRtl ? 'حالة الرسالة' : 'Message status' }}</p>
        <h2 class="st-join-hero__title">{{ $ticket->subject }}</h2>
        <p class="st-join-hero__meta">
            {{ $isRtl ? 'التصنيف' : 'Category' }}: {{ $ticket->inquiryCategory->name ?? '—' }}
            · {{ $isRtl ? 'الحالة' : 'Status' }}: <strong>{{ $statusLabel }}</strong>
        </p>
    </div>
</section>

<section class="st-panel" style="margin-bottom:1.25rem">
    <ol style="list-style:none;padding:0;margin:0;display:grid;gap:.65rem">
        @foreach($steps as $i => $step)
            @php
                $done = $i <= $rank;
                $current = $i === $rank;
            @endphp
            <li style="display:flex;align-items:center;gap:.75rem;opacity:{{ $done ? '1' : '.45' }}">
                <span aria-hidden="true" style="width:1.75rem;height:1.75rem;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:{{ $done ? '#1E4E8C' : '#e5e7eb' }};color:{{ $done ? '#fff' : '#64748b' }};font-size:.8rem;font-weight:700">
                    {{ $done ? '✓' : ($i + 1) }}
                </span>
                <span style="font-weight:{{ $current ? '700' : '500' }}">{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>
    <p style="margin-top:1rem;font-size:.92rem;opacity:.85">
        {{ $isRtl
            ? 'الدعم عبر النموذج قد يستغرق حتى يوم عمل. للحالات العاجلة استخدم واتساب من صفحة التواصل.'
            : 'Form support may take up to one business day. For urgent cases use WhatsApp from the contact page.' }}
        <a href="{{ route('public.contact') }}">{{ $isRtl ? 'صفحة التواصل' : 'Contact page' }}</a>
    </p>
</section>

<section class="st-panel" style="margin-bottom:1.25rem">
    <h3 style="margin:0 0 .75rem;font-size:1.05rem">{{ $isRtl ? 'المحادثة' : 'Conversation' }}</h3>
    <div style="display:grid;gap:.75rem">
        @forelse($ticket->replies as $reply)
            <article style="border:1px solid #e2e8f0;border-radius:.75rem;padding:.85rem;background:{{ $reply->sender_type === 'admin' ? '#ecfdf5' : '#f8fafc' }}">
                <div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.35rem;font-size:.8rem">
                    <strong>{{ $reply->sender_type === 'admin' ? ($isRtl ? 'فريق الدعم' : 'Support team') : ($isRtl ? 'أنت' : 'You') }}</strong>
                    <span>{{ $reply->created_at?->format('Y-m-d H:i') }}</span>
                </div>
                <p style="margin:0;white-space:pre-line">{{ $reply->message }}</p>
            </article>
        @empty
            <p style="opacity:.8">{{ $isRtl ? 'لا ردود بعد — ما زالت رسالتك في قائمة الانتظار.' : 'No replies yet — your message is still in the queue.' }}</p>
        @endforelse
    </div>
</section>

@if(! in_array($ticket->status, ['resolved', 'closed'], true))
    <form action="{{ route('student.support.reply', $ticket) }}" method="POST" class="st-panel">
        @csrf
        <label for="support-reply" style="display:block;font-weight:600;margin-bottom:.35rem">{{ $isRtl ? 'إضافة رد' : 'Add a reply' }}</label>
        <textarea id="support-reply" name="message" rows="4" required style="width:100%;border:1px solid #cbd5e1;border-radius:.65rem;padding:.75rem">{{ old('message') }}</textarea>
        @error('message')<p class="st-flash st-flash--err">{{ $message }}</p>@enderror
        <button type="submit" class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:.75rem">{{ $isRtl ? 'إرسال الرد' : 'Send reply' }}</button>
    </form>
@endif

<p style="margin-top:1rem">
    <a href="{{ route('student.support.index') }}">{{ $isRtl ? '← كل التذاكر' : '← All tickets' }}</a>
</p>
@endsection
