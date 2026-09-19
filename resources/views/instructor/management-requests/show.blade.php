@extends('layouts.app')

@section('title', __('instructor.request_details_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.request_details_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $chip = match ($request->status) {
        'pending' => 'id-chip--warn',
        'approved' => 'id-chip--ok',
        default => 'id-chip--rose',
    };
    $label = match ($request->status) {
        'pending' => __('instructor.pending_review'),
        'approved' => __('instructor.approved'),
        default => __('instructor.rejected'),
    };
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $request->subject }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.my_requests_to_management') }}</p>
            <h2 class="id-hero__title">{{ $request->subject }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $label }}</span>
                <span>{{ $request->created_at->format('Y-m-d H:i') }}</span>
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.management-requests.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back_to_list') }}
            </a>
        </div>
    </section>

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.request_text_label') }}</h2>
            <span class="id-chip {{ $chip }}">{{ $label }}</span>
        </header>
        <p style="margin:0;font-size:14px;font-weight:600;line-height:1.8;color:#3A4A63;white-space:pre-wrap">{{ $request->message }}</p>
    </section>

    @if($request->admin_reply)
        <section class="id-panel" style="border-color:rgba(30,78,140,.2);background:#F7FAFE">
            <header class="id-panel__head">
                <h2>{{ __('instructor.admin_response_label') }}</h2>
            </header>
            <p style="margin:0;font-size:14px;font-weight:600;line-height:1.8;color:#3A4A63;white-space:pre-wrap">{{ $request->admin_reply }}</p>
            <p class="id-field__hint" style="margin-top:12px">
                {{ $request->replied_at?->format('Y-m-d H:i') }}
                @if($request->repliedByUser)
                    — {{ $request->repliedByUser->name }}
                @endif
            </p>
        </section>
    @endif
</div>
@endsection
