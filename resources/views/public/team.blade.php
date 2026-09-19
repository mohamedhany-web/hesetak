@extends('layouts.public')

@section('title', 'فريق حصتك')
@section('meta_description', 'فريق حصتك يعمل على تقديم تجربة دروس خصوصية أونلاين موثوقة للطلاب وأولياء الأمور.')
@section('canonical_url', url('/team'))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">من يقف خلف التجربة؟</p>
    <h1>فريق يبني تعلماً فردياً موثوقاً</h1>
    <p class="mc-lead">نجمع الخبرة الأكاديمية والتشغيل والدعم لنساعد كل طالب على الوصول إلى معلم مناسب، منهج واضح، ومتابعة يفهمها ولي الأمر.</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <article class="mc-track">
        <span class="mc-track__icon"><i class="fas fa-user-check"></i></span>
        <h3>اختيار المعلمين</h3>
        <p>نراجع طلبات المعلمين وخبراتهم قبل إتاحة ملفاتهم وحجوزاتهم على المنصة.</p>
      </article>
      <article class="mc-track mc-track--featured">
        <span class="mc-track__icon"><i class="fas fa-shield-heart"></i></span>
        <h3>جودة ومتابعة</h3>
        <p>نطوّر رحلة واضحة من اختيار المعلم إلى الحصة والتقرير والتقييم، مع دعم عند الحاجة.</p>
      </article>
      <article class="mc-track">
        <span class="mc-track__icon"><i class="fas fa-headset"></i></span>
        <h3>دعم العائلات</h3>
        <p>نساعد الطلاب وأولياء الأمور في اختيار المسار بين الحصص الفردية والمناهج والكورسات.</p>
      </article>
    </div>
  </div>
</section>

<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>هل ترغب في الانضمام كمعلم؟</h2>
        <p>تعرّف على خطوات التقديم والاعتماد، أو تواصل معنا للاستفسار عن المنصة.</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.for-teachers') }}" class="mc-btn mc-btn--secondary">قدّم كمعلم</a>
        <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--ghost-on-dark">تواصل معنا</a>
      </div>
    </div>
  </div>
</section>
@endsection
