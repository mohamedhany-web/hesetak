@extends('layouts.app')

@section('title', 'مواعيد مقترحة من الطلاب')
@section('page_title', 'مواعيد مقترحة من الطلاب')

@section('content')
<div class="id-page">
    @if(session('success'))
        <div class="id-alert id-alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif

    <section class="id-hero" aria-label="مواعيد مقترحة">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.private_lessons') }}</p>
            <h2 class="id-hero__title">مواعيد يقترحها الطلاب عليك</h2>
            <p class="id-hero__meta">اختر المواعيد المناسبة واقبلها، أو ارفض الطلب — يصل الطالب إشعار بقرارك.</p>
        </div>
    </section>

    <section class="id-panel id-panel--wide" aria-label="الطلبات">
        <header class="id-panel__head"><h2>الطلبات</h2></header>

        @forelse($proposals as $proposal)
            @php
                $tz = $proposal->timezone ?: \App\Support\AppTimezone::academy();
                $acceptedIso = collect($proposal->accepted_slots ?? []);
            @endphp
            <article style="border-top:1px solid rgba(128,128,128,.2);padding:1rem 0;display:grid;gap:.75rem">
                <header style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:space-between;align-items:center">
                    <div>
                        <strong>{{ $proposal->student?->name }}</strong>
                        <span class="muted"> · {{ $proposal->servicePackage?->name }}@if($proposal->academicSubject) · {{ $proposal->academicSubject->name }}@endif</span>
                    </div>
                    <span class="id-chip">{{ $proposal->statusLabel() }}</span>
                </header>

                @if($proposal->student_notes)
                    <p class="muted" style="margin:0">ملاحظات الطالب: {{ $proposal->student_notes }}</p>
                @endif

                @if($proposal->isPending())
                    <form method="POST" action="{{ route('instructor.package-slot-proposals.respond', $proposal) }}" style="display:grid;gap:.75rem">
                        @csrf
                        <fieldset style="border:0;padding:0;margin:0;display:grid;gap:.4rem">
                            <legend style="font-weight:600;margin-bottom:.25rem">المواعيد المقترحة (بتوقيت الطالب: {{ \App\Support\AppTimezone::label($tz) }})</legend>
                            @foreach($proposal->proposed_slots as $iso)
                                <label style="display:flex;gap:.5rem;align-items:center">
                                    <input type="checkbox" name="accepted_slots[]" value="{{ $iso }}" checked>
                                    <span class="tabular-nums">{{ \Carbon\Carbon::parse($iso)->timezone($tz)->locale('ar')->translatedFormat('l j F Y — g:i A') }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <textarea name="response_note" rows="2" maxlength="1000" placeholder="ملاحظة للطالب (اختياري)" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid rgba(128,128,128,.35)"></textarea>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                            <button type="submit" name="decision" value="accept" class="id-btn id-btn--gold">قبول المواعيد المحددة</button>
                            <button type="submit" name="decision" value="reject" class="id-btn id-btn--outline">رفض الطلب</button>
                        </div>
                    </form>
                @else
                    <ul style="margin:0;padding-inline-start:1.25rem">
                        @foreach($proposal->proposed_slots as $iso)
                            <li class="tabular-nums">
                                {{ \Carbon\Carbon::parse($iso)->timezone($tz)->locale('ar')->translatedFormat('l j F Y — g:i A') }}
                                — {{ $acceptedIso->contains($iso) ? 'مقبول' : 'غير مقبول' }}
                            </li>
                        @endforeach
                    </ul>
                    @if($proposal->response_note)
                        <p class="muted" style="margin:0">ملاحظتك: {{ $proposal->response_note }}</p>
                    @endif
                @endif
            </article>
        @empty
            <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-calendar-plus"></i></span>
                <p>لا توجد مواعيد مقترحة من الطلاب حتى الآن.</p>
            </div>
        @endforelse

        @if($proposals->hasPages())
            <div class="id-pager">{{ $proposals->links() }}</div>
        @endif
    </section>
</div>
@endsection
