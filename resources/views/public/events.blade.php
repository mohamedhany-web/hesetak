@extends('layouts.public')

@section('title', 'التعلم المباشر مع حصتك')
@section('meta_description', 'حصتك متاحة للتعلم أونلاين طوال العام عبر حصص فردية ومناهج وكورسات.')
@section('canonical_url', url('/events'))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">تعلم مستمر</p>
    <h1>لا تنتظر موعد فعالية لتبدأ</h1>
    <p class="mc-lead">حصتك منصة تعليمية تعمل طوال العام. اختر معلماً معتمداً واحجز حصة فردية أونلاين في الوقت المناسب لك.</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">ابحث عن معلم</a>
      <a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--lg mc-btn--outline">استكشف المناهج</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-steps">
      <article class="mc-step"><div class="mc-step__n">1</div><h3>حدد احتياجك</h3><p>اختر المادة والمرحلة ونوع المنهج أو الكورس المناسب.</p></article>
      <article class="mc-step"><div class="mc-step__n">2</div><h3>اختر المعلم</h3><p>راجع الملفات والتخصصات والمواعيد المتاحة قبل الحجز.</p></article>
      <article class="mc-step"><div class="mc-step__n">3</div><h3>ابدأ أونلاين</h3><p>احضر حصتك الفردية وتابع التقارير والتقدم من حسابك.</p></article>
    </div>
  </div>
</section>
@endsection
