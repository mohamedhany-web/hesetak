@extends('layouts.mycourses-public')

@section('content')
@php
  $brand = __('landing.nav.brand');
  $isRtl = app()->getLocale() === 'ar';
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('public.refund_page_title') }}</p>
    <h1>
      {{ $isRtl ? 'سياسة الاسترجاع على' : 'Refund policy on' }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    <p class="mc-lead">
      {{ $isRtl
        ? 'نلتزم بوضوح شروط الاسترجاع لأولياء الأمور والطلاب عند شراء باقات الحصص والكورسات.'
        : 'Clear refund rules for parents and students when buying session packages and courses.' }}
    </p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ __('public.contact_page_title') }}</a>
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'تصفّح الباقات' : 'See packages' }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-legal-intro">
      <span class="mc-legal-intro__icon" aria-hidden="true"><i class="fas fa-rotate-left"></i></span>
      <p>
        {{ $isRtl
          ? 'إذا لم تكن راضياً عن الخدمة، يمكنك طلب استرجاع وفق الشروط أدناه خلال المدة المحددة من تاريخ الشراء، مع مراعاة ما يُعرض عند إتمام الطلب.'
          : 'If you are not satisfied, you may request a refund under the rules below within the stated period from purchase, subject to what is shown at checkout.' }}
      </p>
    </div>

    <div class="mc-legal-grid" style="margin-top:1.5rem">
      <article class="mc-legal-card">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
          <h2>{{ $isRtl ? 'شروط الاسترجاع' : 'Refund conditions' }}</h2>
        </div>
        <ul class="mc-legal-list">
          <li>{{ $isRtl ? 'يُقدَّم الطلب خلال 30 يوماً من تاريخ الشراء.' : 'Request within 30 days of purchase.' }}</li>
          <li>{{ $isRtl ? 'يُراجع الطلب من فريق الدعم قبل التنفيذ.' : 'Support reviews the request before processing.' }}</li>
          <li>{{ $isRtl ? 'قد تختلف التفاصيل حسب نوع الباقة أو الكورس المعروض عند الشراء.' : 'Details may vary by package or course shown at purchase.' }}</li>
        </ul>
      </article>
      <article class="mc-legal-card">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
          <h2>{{ $isRtl ? 'حالات غير قابلة للاسترجاع' : 'Non-refundable cases' }}</h2>
        </div>
        <ul class="mc-legal-list">
          <li>{{ $isRtl ? 'استهلاك جزء كبير من رصيد الحصص أو المحتوى.' : 'Most session credits or content already used.' }}</li>
          <li>{{ $isRtl ? 'مخالفة شروط الاستخدام أو إساءة استخدام الحساب.' : 'Terms violations or account misuse.' }}</li>
          <li>{{ $isRtl ? 'طلبات بعد انتهاء المهلة المحددة.' : 'Requests after the stated deadline.' }}</li>
        </ul>
      </article>
      <article class="mc-legal-card mc-legal-card--wide">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-headset"></i></span>
          <h2>{{ $isRtl ? 'كيف تطلب الاسترجاع؟' : 'How to request a refund' }}</h2>
        </div>
        <p>
          {{ $isRtl
            ? 'تواصل معنا عبر صفحة التواصل أو واتساب مع رقم الطلب وتفاصيل المشكلة. سنراجع الطلب ونرد خلال أيام العمل.'
            : 'Contact us via the contact page or WhatsApp with your order number and issue details. We review and reply within business days.' }}
        </p>
      </article>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ $isRtl ? 'تحتاج مساعدة؟' : 'Need help?' }}</h2>
        <p>{{ $isRtl ? 'فريق حصتك جاهز يوضح لك وضع طلبك خطوة بخطوة.' : 'The Hesetak team can walk you through your request step by step.' }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('public.contact_page_title') }}</a>
        <a href="{{ route('public.faq') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('public.faq_page_title') }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
