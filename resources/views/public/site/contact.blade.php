@extends('layouts.mycourses-public')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $fields = $content['fields'] ?? [];
  $footer = \App\Services\PublicFooterSettings::payload();
  $email = trim((string) ($footer['email'] ?? '')) ?: trim((string) ($content['email'] ?? ''));
  $phone = trim((string) ($footer['phone'] ?? '')) ?: trim((string) ($content['phone'] ?? ''));
  $waUrl = trim((string) ($footer['whatsapp_url'] ?? ''));
  $phoneTel = $phone !== '' ? preg_replace('/\s+/', '', $phone) : '';
  $brand = __('landing.nav.brand');
@endphp

<section class="mc-page-hero mc-contact-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $content['kicker'] ?? ($isRtl ? 'دعم حصتك' : 'Hesetak support') }}</p>
    <h1>
      {{ $content['title'] ?? __('site.nav.contact') }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    @if(!empty($content['lead']))
      <p class="mc-lead">{{ $content['lead'] }}</p>
    @endif
    <div class="mc-hero__actions">
      @if($waUrl !== '')
        <a href="{{ $waUrl }}" class="mc-btn mc-btn--lg mc-btn--primary" target="_blank" rel="noopener noreferrer">
          <i class="fab fa-whatsapp" aria-hidden="true"></i>
          {{ $content['cta_whatsapp'] ?? ($isRtl ? 'تحدّث عبر واتساب' : 'Chat on WhatsApp') }}
        </a>
      @endif
      <a href="#contact-form" class="mc-btn mc-btn--lg mc-btn--outline">
        {{ $content['cta_form'] ?? ($isRtl ? 'أرسل رسالة' : 'Send a message') }}
      </a>
    </div>
    @if(!empty($content['trust']))
      <ul class="mc-contact-trust" aria-label="{{ $isRtl ? 'مميزات الدعم' : 'Support highlights' }}">
        @foreach($content['trust'] as $item)
          <li><i class="fas fa-check" aria-hidden="true"></i> {{ $item }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

<section class="mc-section mc-section--tight">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ $content['channels_kicker'] ?? ($isRtl ? 'اختر القناة' : 'Pick a channel') }}</p>
        <h2 class="mc-title">{{ $content['channels_title'] ?? ($isRtl ? 'تواصل بالطريقة الأنسب لك' : 'Reach us the way that works for you') }}</h2>
        <p class="mc-lead">{{ $content['channels_lead'] ?? ($isRtl ? 'واتساب للرد السريع، أو بريد ورسالة مفصّلة عبر النموذج.' : 'WhatsApp for a quick reply, or email and a detailed form message.') }}</p>
      </div>
    </div>

    <div class="mc-tracks mc-contact-channels">
      @if($waUrl !== '')
        <a class="mc-track mc-track--featured" href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer">
          <span class="mc-track__icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
          <h3>{{ $isRtl ? 'واتساب' : 'WhatsApp' }}</h3>
          <p>{{ $content['channel_wa_body'] ?? ($isRtl ? 'الأفضل لأولياء الأمور: رد سريع ومواعيد مرنة.' : 'Best for parents: a fast reply and flexible scheduling.') }}</p>
          <span class="mc-track__cta">{{ $isRtl ? 'افتح واتساب ←' : 'Open WhatsApp →' }}</span>
        </a>
      @endif

      @if($email !== '')
        <a class="mc-track" href="mailto:{{ $email }}">
          <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
          <h3>{{ $isRtl ? 'البريد' : 'Email' }}</h3>
          <p>{{ $content['channel_email_body'] ?? ($isRtl ? 'أرسل تفاصيل طلبك وسنرد خلال يوم عمل.' : 'Send details and we reply within one business day.') }}</p>
          <span class="mc-track__cta" dir="ltr">{{ $email }}</span>
        </a>
      @endif

      @if($phone !== '')
        <a class="mc-track" href="tel:{{ $phoneTel }}">
          <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-phone"></i></span>
          <h3>{{ $isRtl ? 'الهاتف' : 'Phone' }}</h3>
          <p>{{ $content['channel_phone_body'] ?? ($isRtl ? 'اتصل بنا في أوقات الدعم اليومية.' : 'Call us during daily support hours.') }}</p>
          <span class="mc-track__cta" dir="ltr">{{ $phone }}</span>
        </a>
      @endif
    </div>
  </div>
</section>

<section class="mc-section" id="contact-form">
  <div class="mc-container mc-contact-grid">
    <aside class="mc-contact-aside">
      <p class="mc-eyebrow">{{ $content['form_kicker'] ?? ($isRtl ? 'رسالة مكتوبة' : 'Written message') }}</p>
      <h2 class="mc-title">{{ $content['form_title'] ?? ($isRtl ? 'أرسل رسالتك عبر حصتك' : 'Send your message via Hesetak') }}</h2>
      <p class="mc-lead">{{ $content['form_lead'] ?? ($isRtl ? 'احجز، استفسر عن منهج، أو اطلب مساعدة في حسابك، وسنعود إليك.' : 'Book, ask about a curriculum, or get help with your account — we will reply.') }}</p>

      <ul class="mc-contact-aside__list">
        @if($email !== '')
          <li>
            <span class="mc-contact-aside__label">{{ $isRtl ? 'البريد' : 'Email' }}</span>
            <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a>
          </li>
        @endif
        @if($phone !== '')
          <li>
            <span class="mc-contact-aside__label">{{ $isRtl ? 'الهاتف' : 'Phone' }}</span>
            <a href="tel:{{ $phoneTel }}" dir="ltr">{{ $phone }}</a>
          </li>
        @endif
        @if(!empty($content['region']))
          <li>
            <span class="mc-contact-aside__label">{{ $isRtl ? 'الحضور' : 'Presence' }}</span>
            <span>{{ $content['region'] }}</span>
          </li>
        @endif
      </ul>

      @foreach(($content['sections'] ?? []) as $section)
        <div class="mc-contact-aside__note">
          <h3>{{ $section['title'] ?? '' }}</h3>
          <p>{{ $section['body'] ?? '' }}</p>
        </div>
      @endforeach
    </aside>

    <div class="mc-contact-form">
      @if(session('status'))
        <div class="lasles-auth-alert lasles-auth-alert--ok" role="status">{{ session('status') }}</div>
      @endif
      @if($errors->any())
        <div class="lasles-auth-alert lasles-auth-alert--err" role="alert">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('public.contact.store') }}" novalidate>
        @csrf
        <div class="lasles-auth-field">
          <label for="name">{{ $fields['name'] ?? ($isRtl ? 'الاسم' : 'Name') }}</label>
          <input type="text" name="name" id="name" value="{{ old('name') }}" required autocomplete="name">
        </div>
        <div class="lasles-auth-field">
          <label for="email">{{ $fields['email'] ?? ($isRtl ? 'البريد الإلكتروني' : 'Email') }}</label>
          <input type="email" name="email" id="email" value="{{ old('email') }}" required dir="ltr" autocomplete="email">
        </div>
        <div class="lasles-auth-field">
          <label for="phone">{{ $fields['phone'] ?? ($isRtl ? 'الجوال' : 'Phone') }}</label>
          <input type="text" name="phone" id="phone" value="{{ old('phone') }}" dir="ltr" autocomplete="tel">
        </div>
        <div class="lasles-auth-field">
          <label for="topic">{{ $fields['topic'] ?? ($isRtl ? 'الموضوع' : 'Topic') }}</label>
          <input type="text" name="topic" id="topic" value="{{ old('topic') }}" placeholder="{{ $fields['topic_hint'] ?? ($isRtl ? 'مثال: حجز حصة، منهج، باقة' : 'e.g. booking, curriculum, package') }}">
        </div>
        <div class="lasles-auth-field">
          <label for="message">{{ $fields['message'] ?? ($isRtl ? 'الرسالة' : 'Message') }}</label>
          <textarea name="message" id="message" rows="5" required>{{ old('message') }}</textarea>
        </div>
        <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary mc-contact-form__submit">
          {{ $fields['submit'] ?? ($isRtl ? 'إرسال الرسالة' : 'Send message') }}
        </button>
      </form>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ $content['footer_cta_title'] ?? ($isRtl ? 'جاهز تحجز حصة؟' : 'Ready to book a session?') }}</h2>
        <p>{{ $content['footer_cta_lead'] ?? ($isRtl ? 'ارجع لدليل المعلمين المعتمدين واختر الموعد المناسب لابنك.' : 'Browse approved teachers and pick a time that fits your child.') }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.hero.cta_primary') }}</a>
        <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $isRtl ? 'تصفّح الباقات' : 'See packages' }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
