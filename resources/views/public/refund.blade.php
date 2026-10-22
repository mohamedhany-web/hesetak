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
      {{ $isRtl ? 'سياسة الإلغاء والاسترداد في' : 'Cancellation and refunds at' }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    <p class="mc-lead">
      {{ $isRtl
        ? 'سياسة الاسترداد للحصص الفردية قائمة على حجز ساعات المعلمين مسبقاً. السجل التجاري: 705518778.'
        : 'Refunds for one-to-one lessons follow teachers’ hours booked in advance. Commercial registration: 705518778.' }}
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
          ? 'تُقدَّم طلبات الاسترداد لخدمة العملاء عبر الواتساب الرسمي أو البريد الإلكتروني المعتمد، ولا يُعتد بطلب شفهي مع المعلم. يُنفَّذ الاسترداد المعتمد خلال 14 يوم عمل.'
          : 'Refund requests go to customer service on the official WhatsApp or approved email. A verbal request to the teacher is not valid. Approved refunds are completed within 14 business days.' }}
      </p>
    </div>

    <div class="mc-legal-grid" style="margin-top:1.5rem">
      <article class="mc-legal-card">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
          <h2>{{ $isRtl ? 'قبل بدء الدراسة' : 'Before study starts' }}</h2>
        </div>
        <ul class="mc-legal-list">
          <li>{{ $isRtl ? 'إذا طُلب الاسترداد قبل موعد الحصة الأولى بـ 24 ساعة على الأقل، يُرد المبلغ كاملاً.' : 'A request at least 24 hours before the first lesson is refunded in full.' }}</li>
          <li>{{ $isRtl ? 'يسري ذلك مع وجود حصة أولى أساسية أو تجريبية، أو قبل بدء الدراسة بدونها.' : 'This covers a first standard lesson, a demo, or the period before study starts.' }}</li>
        </ul>
      </article>
      <article class="mc-legal-card">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
          <h2>{{ $isRtl ? 'بعد الحصة الأولى وقبل الثانية' : 'After the first lesson, before the second' }}</h2>
        </div>
        <ul class="mc-legal-list">
          <li>{{ $isRtl ? 'يُخصم سعر الحصة الأولى الفردي المنفصل (خارج خصم الباقة).' : 'The first lesson is deducted at its separate individual price, outside the package discount.' }}</li>
          <li>{{ $isRtl ? 'تُخصم 15٪ رسوم إدارية وتشغيلية من إجمالي المبلغ، وأي مصاريف تحويل بنكي أو إلكتروني فعلية.' : 'A 15% administrative and operating fee on the total, plus any actual bank or electronic transfer charges, is deducted.' }}</li>
          <li>{{ $isRtl ? 'يمكن تحويل الرصيد المتبقي إلى دورة أخرى. لا يتم تغيير المعلم بعد الحصة الأولى.' : 'Remaining balance may be moved to another course. The teacher is not changed after the first lesson.' }}</li>
        </ul>
      </article>
      <article class="mc-legal-card mc-legal-card--wide">
        <div class="mc-legal-card__head">
          <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-headset"></i></span>
          <h2>{{ $isRtl ? 'من الحصة الثانية وحالات الأكاديمية' : 'From the second lesson, and academy-side cases' }}</h2>
        </div>
        <p>
          {{ $isRtl
            ? 'بدءاً من الحصة الثانية لا يوجد استرداد نقدي، ويمكن تحويل الرصيد المتبقي إلى دورة أخرى. الدعم الإضافي برسوم يتم الاتفاق عليها. إذا تعذّر توفير مدرس مؤهل، أو تكرر الإلغاء، أو ثبت إخلال من طرف المدرس، يُرد الرصيد المتبقي بعد توفير بديل مناسب، والموافقة على أي استرداد إضافي للأكاديمية. التنفيذ خلال 14 يوم عمل.'
            : 'From the second lesson there is no cash refund; remaining balance may be moved to another course. Extra support is charged by agreement. If a qualified teacher cannot be provided, cancellations repeat, or a teacher breach is established, the remaining balance is refunded after a suitable alternative is offered. Extra refunds need academy approval and are completed within 14 business days.' }}
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
