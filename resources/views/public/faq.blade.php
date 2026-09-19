@extends('layouts.public')

@section('title', __('public.faq_page_title'))
@section('meta_description', __('public.faq_meta_description', ['brand' => __('landing.nav.brand')]))
@section('canonical_url', url('/faq'))

@section('content')
@php
  $groupedDefaults = collect($defaultFaqs ?? [])->groupBy('category');
  $hasDatabaseFaqs = isset($faqs) && $faqs->isNotEmpty();
@endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">مركز المساعدة</p>
    <h1>{{ __('public.faq_page_title') }}</h1>
    <p class="mc-lead">إجابات واضحة عن الحصص الفردية والمناهج والكورسات والحجز والدفع.</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--primary">تواصل معنا</a>
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--outline">دليل المعلمين</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="max-width:900px">
    @if($hasDatabaseFaqs)
      @foreach($faqs as $category => $items)
        <div class="mc-section-head mc-section-head--tight">
          <h2>{{ $category ?: 'أسئلة عامة' }}</h2>
        </div>
        <div class="mc-grid" style="margin-bottom:2rem">
          @foreach($items as $faq)
            <details class="mc-card" style="padding:1.1rem">
              <summary style="cursor:pointer;font-weight:800;color:var(--mc-ink)">{{ $faq->question }}</summary>
              <p style="color:var(--mc-muted);line-height:1.8;margin:1rem 0 0">{!! nl2br(e($faq->answer)) !!}</p>
            </details>
          @endforeach
        </div>
      @endforeach
    @endif

    @foreach($groupedDefaults as $category => $items)
      <div class="mc-section-head mc-section-head--tight"><h2>{{ $category ?: 'عن حصتك' }}</h2></div>
      <div class="mc-grid" style="margin-bottom:2rem">
        @foreach($items as $item)
          <details class="mc-card" style="padding:1.1rem">
            <summary style="cursor:pointer;font-weight:800;color:var(--mc-ink)">{{ $item['question'] }}</summary>
            <p style="color:var(--mc-muted);line-height:1.8;margin:1rem 0 0">{!! nl2br(e($item['answer'] ?? '')) !!}</p>
          </details>
        @endforeach
      </div>
    @endforeach

    @if(!$hasDatabaseFaqs && $groupedDefaults->isEmpty())
      <div class="mc-empty"><p>لم تُنشر أسئلة بعد.</p><a href="{{ route('public.contact') }}" class="mc-btn mc-btn--md mc-btn--primary">اسأل فريقنا</a></div>
    @endif
  </div>
</section>
@endsection
