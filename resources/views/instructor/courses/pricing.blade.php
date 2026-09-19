@extends('layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'تسعير الكورس' : 'Course pricing')
@section('page_title', app()->getLocale() === 'ar' ? 'تسعير الكورس' : 'Course pricing')

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $usdPrice = old('price_usd', $course->price_usd ?? $course->price_egp ?? $course->price);
    $usdSale = old('price_usd_after_discount', $course->price_usd_after_discount ?? $course->price_egp_after_discount ?? $course->price_after_discount);
@endphp

<div class="su-page" style="max-width:720px">
    <div class="su-page-head">
        <div class="min-w-0">
            <nav class="su-crumb-inline" aria-label="breadcrumb">
                <a href="{{ route('instructor.courses.index') }}">{{ __('instructor.my_courses') }}</a>
                <span>/</span>
                <a href="{{ route('instructor.courses.show', $course) }}">{{ $course->title }}</a>
                <span>/</span>
                <strong style="color:var(--su-ink)">{{ $isRtl ? 'التسعير' : 'Pricing' }}</strong>
            </nav>
            <h1 class="su-page-head__title">
                <i class="fas fa-tags su-page-head__ico" aria-hidden="true"></i>
                {{ $isRtl ? 'أسعار الكورس المسجّل' : 'Recorded course pricing' }}
            </h1>
            <p class="su-page-head__sub">
                {{ $isRtl ? 'جميع الأسعار بالريال السعودي (ر.س).' : 'All prices are in Saudi Riyal (SAR).' }}
            </p>
        </div>
        <div class="su-page-head__actions">
            <a href="{{ route('instructor.courses.show', $course) }}" class="su-btn">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="su-card" style="margin-bottom:16px;border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.08)">
            <p style="margin:0;font-size:13px;color:#b91c1c">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('instructor.courses.pricing.update', $course) }}" class="su-card">
        @csrf
        @method('PUT')

        <div class="su-form-grid" style="grid-template-columns:1fr 1fr;margin-bottom:16px">
            <div class="su-field">
                <label for="price_usd">{{ $isRtl ? 'السعر بالريال (ر.س)' : 'Price (SAR)' }}</label>
                <input type="number" step="0.01" min="0" name="price_usd" id="price_usd"
                       value="{{ $usdPrice }}" class="su-input">
            </div>
            <div class="su-field">
                <label for="price_usd_after_discount">{{ $isRtl ? 'بعد الخصم (ر.س)' : 'After discount (SAR)' }}</label>
                <input type="number" step="0.01" min="0" name="price_usd_after_discount" id="price_usd_after_discount"
                       value="{{ $usdSale }}" class="su-input">
            </div>
        </div>

        <div class="su-page-head__actions">
            <button type="submit" class="su-btn su-btn--primary">
                <i class="fas fa-save" aria-hidden="true"></i>
                {{ $isRtl ? 'حفظ الأسعار' : 'Save pricing' }}
            </button>
        </div>
    </form>
</div>
@endsection
