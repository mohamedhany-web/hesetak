<section class="lasles-pricing" id="pricing">
  <div class="lasles-container">
    <h2 class="lasles-section-title">{{ $isRtl ? 'اختر باقتك' : 'Choose Your Plan' }}</h2>
    <p class="lasles-section-lead">
      {{ $isRtl
        ? 'اختر الباقة الأنسب لك واستكشف أدوات التطوير المهني بثقة.'
        : "Let's choose the package that is best for you and explore it happily and cheerfully." }}
    </p>
    <div class="lasles-plans">
      @php
        $plans = [
          [
            'art' => 'plan-free.svg',
            'name' => $isRtl ? 'باقة مجانية' : 'Free Plan',
            'price' => $isRtl ? 'مجاني' : 'Free',
            'featured' => false,
            'features' => $isRtl
              ? ['تشخيص أساسي', 'مكتبة ممارسات محدودة', 'بدون سجلات زائدة', 'يعمل على كل الأجهزة']
              : ['Unlimited Bandwitch', 'Encrypted Connection', 'No Traffic Logs', 'Works on All Devices'],
          ],
          [
            'art' => 'plan-standard.svg',
            'name' => $isRtl ? 'باقة قياسية' : 'Standard Plan',
            'price_html' => $isRtl ? '<b>$9</b> <span>/ شهر</span>' : '<b>$9</b> <span>/ mo</span>',
            'featured' => false,
            'features' => $isRtl
              ? ['تشخيص متقدم', 'ممارسات وأدوات', 'تحديات تطبيقية', 'يعمل على كل الأجهزة', 'متابعة تقدّم']
              : ['Unlimited Bandwitch', 'Encrypted Connection', 'Yes Traffic Logs', 'Works on All Devices', 'Connect Anyware'],
          ],
          [
            'art' => 'plan-premium.svg',
            'name' => $isRtl ? 'باقة مميزة' : 'Premium Plan',
            'price_html' => $isRtl ? '<b>$12</b> <span>/ شهر</span>' : '<b>$12</b> <span>/ mo</span>',
            'featured' => true,
            'features' => $isRtl
              ? ['كل ميزات القياسية', 'مكتبة كاملة', 'تحديات متقدمة', 'قياس تقدّم تفصيلي', 'دعم أولوية', 'مسارات مخصّصة']
              : ['Unlimited Bandwitch', 'Encrypted Connection', 'Yes Traffic Logs', 'Works on All Devices', 'Connect Anyware', 'Get New Features'],
          ],
        ];
      @endphp
      @foreach($plans as $plan)
        <article class="lasles-plan {{ $plan['featured'] ? 'is-featured' : '' }}">
          <img class="lasles-plan__art" src="{{ $img($plan['art']) }}" width="145" height="165" alt="" loading="lazy">
          <h3 class="lasles-plan__name">{{ $plan['name'] }}</h3>
          <ul class="lasles-plan__list">
            @foreach($plan['features'] as $f)
              <li><img src="{{ $img('check-list.svg') }}" width="20" height="20" alt="">{{ $f }}</li>
            @endforeach
          </ul>
          <div class="lasles-plan__footer">
            <p class="lasles-plan__price">
              @if(!empty($plan['price_html']))
                {!! $plan['price_html'] !!}
              @else
                {{ $plan['price'] }}
              @endif
            </p>
            <a href="{{ route('register') }}" class="{{ $plan['featured'] ? 'lasles-btn-primary' : 'lasles-btn-outline' }}">
              {{ $isRtl ? 'اختيار' : 'Select' }}
            </a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
