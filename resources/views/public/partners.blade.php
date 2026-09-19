@extends('layouts.public')

@section('title', 'الشراكات مع حصتك')
@section('meta_description', 'تعاون مع حصتك لتوسيع الوصول إلى دروس خصوصية أونلاين ومحتوى تعليمي موثوق.')
@section('canonical_url', url('/partners'))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">شراكات تعليمية</p>
    <h1>نبني أثراً تعليمياً معاً</h1>
    <p class="mc-lead">نرحب بالتعاون مع المدارس والمؤسسات والمبادرات التي تريد إتاحة تعليم فردي مرن ومحتوى يخدم الطلاب في أمريكا ومصر والسعودية.</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--primary">ناقش فرصة شراكة</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <article class="mc-track">
        <span class="mc-track__icon"><i class="fas fa-school"></i></span>
        <h3>دعم المناهج</h3>
        <p>مسارات تعليمية تساعد الطلاب على فهم موادهم مع معلمين متخصصين ومواعيد مرنة.</p>
      </article>
      <article class="mc-track mc-track--featured">
        <span class="mc-track__icon"><i class="fas fa-handshake"></i></span>
        <h3>مبادرات مشتركة</h3>
        <p>برامج مخصصة وفق الهدف والفئة المستفيدة، مع نطاق واضح ومؤشرات متابعة قابلة للقياس.</p>
      </article>
      <article class="mc-track">
        <span class="mc-track__icon"><i class="fas fa-book-open"></i></span>
        <h3>محتوى وكورسات</h3>
        <p>تعاون في تقديم كورسات مستقلة أو موارد تعليمية ذات قيمة حقيقية للطالب والأسرة.</p>
      </article>
    </div>
  </div>
</section>
@endsection
