@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $package = $package ?? null;
  $subjects = $subjects ?? collect();
  $teachersBySubject = $teachersBySubject ?? [];
  $checkoutUrl = $checkoutUrl ?? route('public.pricing');
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'تفاصيل الباقة' : 'Package details' }}</p>
    <h1>{{ $package->name }}</h1>
    @if(filled($package->tagline))
      <p class="mc-lead">{{ $package->tagline }}</p>
    @elseif(filled($package->description))
      <p class="mc-lead">{{ \Illuminate\Support\Str::limit(strip_tags($package->description), 180) }}</p>
    @endif
    <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.65rem">
      <a href="{{ $checkoutUrl }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'متابعة الشراء' : 'Continue to checkout' }}</a>
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'كل الباقات' : 'All packages' }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.5rem">
    <article class="mc-card" style="padding:1.25rem">
      <h2 class="mc-title" style="font-size:1.15rem">{{ $isRtl ? 'محتوى الباقة' : 'What’s included' }}</h2>
      <ul style="margin:.75rem 0 0;padding-inline-start:1.1rem;line-height:1.7">
        <li>{{ $isRtl ? 'عدد الحصص:' : 'Sessions:' }} <strong>{{ (int) $package->units_count }}</strong></li>
        @if($package->session_minutes)
          <li>{{ $isRtl ? 'مدة الحصة:' : 'Session length:' }} <strong>{{ (int) $package->session_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</strong></li>
        @endif
        @if($package->academicYear)
          <li>{{ $isRtl ? 'المرحلة:' : 'Stage:' }} <strong>{{ $package->academicYear->name }}</strong></li>
        @endif
        @if($subjects->isNotEmpty())
          <li>{{ $isRtl ? 'المواد:' : 'Subjects:' }} <strong>{{ $subjects->pluck('name')->implode($isRtl ? '، ' : ', ') }}</strong></li>
        @endif
        <li>{{ $isRtl ? 'السعر:' : 'Price:' }} <strong>{{ $package->formattedPrice() }}</strong></li>
      </ul>
      @if(is_array($package->features) && count($package->features))
        <ul style="margin:1rem 0 0;padding-inline-start:1.1rem;line-height:1.7">
          @foreach(array_slice($package->features, 0, 8) as $feat)
            <li>{{ is_string($feat) ? $feat : (string) $feat }}</li>
          @endforeach
        </ul>
      @endif
    </article>

    @forelse($subjects as $subject)
      @php $teachers = $teachersBySubject[(int) $subject->id] ?? collect(); @endphp
      <article class="mc-card" style="padding:1.25rem">
        <h2 class="mc-title" style="font-size:1.1rem">{{ $isRtl ? 'معلمون لمادة' : 'Teachers for' }} {{ $subject->name }}</h2>
        @if($teachers->isEmpty())
          <p class="mc-lead" style="margin-top:.5rem">{{ $isRtl ? 'لا يوجد معلمون منشورون لهذه المادة حالياً — يمكنك الشراء ثم اختيار معلم لاحقاً.' : 'No published teachers for this subject yet — you can buy and pick a teacher later.' }}</p>
          <a href="{{ route('public.instructors.index', ['skill' => $subject->slug]) }}" class="mc-btn mc-btn--sm mc-btn--outline" style="margin-top:.75rem">{{ $isRtl ? 'تصفح دليل المعلمين' : 'Browse teachers' }}</a>
        @else
          <div class="mc-catalog-grid" style="margin-top:1rem">
            @foreach($teachers as $profile)
              @php $u = $profile->user; @endphp
              @if($u)
                <article class="mc-card">
                  <div class="mc-card__body">
                    <h3 class="mc-card__title"><a href="{{ route('public.instructors.show', $u) }}">{{ $u->name }}</a></h3>
                    @if(filled($profile->headline))
                      <p style="margin:.35rem 0;color:var(--mc-muted);font-size:.9rem">{{ \Illuminate\Support\Str::limit($profile->headline, 90) }}</p>
                    @endif
                    <a href="{{ route('public.instructors.show', $u) }}" class="mc-btn mc-btn--sm mc-btn--primary" style="margin-top:.75rem">{{ $isRtl ? 'الملف والمواعيد' : 'Profile & slots' }}</a>
                  </div>
                </article>
              @endif
            @endforeach
          </div>
        @endif
      </article>
    @empty
      <article class="mc-card" style="padding:1.25rem">
        <p class="mc-lead">{{ $isRtl ? 'باقة عامة — اختر المادة والمعلم بعد الشراء أو من دليل المعلمين.' : 'General package — pick subject and teacher after purchase or from the directory.' }}</p>
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--sm mc-btn--outline" style="margin-top:.75rem">{{ $isRtl ? 'دليل المعلمين' : 'Teachers directory' }}</a>
      </article>
    @endforelse
  </div>
</section>
@endsection
