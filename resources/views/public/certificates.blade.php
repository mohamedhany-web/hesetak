@extends('layouts.public')

@section('title', 'شهادات الكورسات')
@section('meta_description', 'شهادات إتمام رقمية للكورسات المؤهلة على حصتك مع إمكانية التحقق من صحتها.')
@section('canonical_url', url('/certificates'))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">إنجاز موثق</p>
    <h1>شهادات إتمام للكورسات المؤهلة</h1>
    <p class="mc-lead">عند إكمال متطلبات كورس مؤهل، تصدر لك شهادة رقمية تحمل بيانات تحقق يمكن الرجوع إليها من أي مكان.</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.certificates.verify') }}" class="mc-btn mc-btn--lg mc-btn--primary">تحقق من شهادة</a>
      <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--lg mc-btn--outline">استكشف الكورسات</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <article class="mc-track"><span class="mc-track__icon"><i class="fas fa-list-check"></i></span><h3>متطلبات واضحة</h3><p>تظهر شروط الإكمال داخل الكورس حتى تعرف ما يلزم قبل إصدار الشهادة.</p></article>
      <article class="mc-track mc-track--featured"><span class="mc-track__icon"><i class="fas fa-certificate"></i></span><h3>نسخة رقمية</h3><p>شهادة تحمل اسم المتعلم والكورس وتاريخ الإصدار ورمز التحقق.</p></article>
      <article class="mc-track"><span class="mc-track__icon"><i class="fas fa-shield-check"></i></span><h3>تحقق مباشر</h3><p>أدخل رمز الشهادة في صفحة التحقق للتأكد من صحة بياناتها.</p></article>
    </div>
  </div>
</section>
@endsection
