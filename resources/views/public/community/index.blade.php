@extends('layouts.public')

@section('title', 'مجتمع حصتك')

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">موارد المجتمع</p>
    <h1>مساحة مشاركة المعرفة في حصتك</h1>
    <p class="mc-lead">موارد تعليمية وتجارب يشاركها المساهمون لدعم التعلم، مع بقاء الدروس الفردية والمناهج والكورسات هي مسارات حصتك الأساسية.</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <a href="{{ route('community.data.index') }}" class="mc-track"><span class="mc-track__icon"><i class="fas fa-database"></i></span><h3>مجموعات البيانات</h3><p>مواد منشورة للبحث والتجربة والتعلم.</p><span class="mc-track__cta">استكشف البيانات ←</span></a>
      <a href="{{ route('community.models.index') }}" class="mc-track"><span class="mc-track__icon"><i class="fas fa-cubes"></i></span><h3>نماذج المجتمع</h3><p>نماذج وملفات يشاركها المساهمون مع وصف الاستخدام.</p><span class="mc-track__cta">استكشف النماذج ←</span></a>
      <a href="{{ route('community.contributors.index') }}" class="mc-track"><span class="mc-track__icon"><i class="fas fa-users"></i></span><h3>المساهمون</h3><p>تعرّف على المشاركين في إثراء موارد المجتمع.</p><span class="mc-track__cta">عرض المساهمين ←</span></a>
    </div>
  </div>
</section>

<section class="mc-section mc-section--muted">
  <div class="mc-container"><div class="mc-cta"><div><h2>ابدأ من مسار التعلم الأساسي</h2><p>اختر معلماً لحصة فردية، أو استكشف المناهج والكورسات المتاحة.</p></div><div class="mc-cta__actions"><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--secondary">ابحث عن معلم</a><a href="{{ route('public.courses') }}" class="mc-btn mc-btn--ghost-on-dark">الكورسات</a></div></div></div>
</section>
@endsection
