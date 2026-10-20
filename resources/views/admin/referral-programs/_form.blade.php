@php
    $program = $program ?? null;
    $scopes = $scopes ?? \App\Models\ServicePackage::scopes();
    $fieldClass = $fieldClass ?? 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = $areaClass ?? 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = $labelClass ?? 'mb-1.5 block text-xs font-medium text-muted';
    $v = fn ($key, $default = null) => old($key, $program?->{$key} ?? $default);
    $rewardMode = $v('reward_mode', 'credits');
@endphp

<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
    <div class="border-b border-line px-4 py-4 sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-info-circle text-sm"></i></span>
            <div>
                <h3 class="text-base font-semibold text-ink">البيانات الأساسية</h3>
                <p class="mt-0.5 text-xs text-muted">اسم البرنامج والوصف</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-5 p-4 sm:p-5">
        <div>
            <label for="name" class="{{ $labelClass }}">اسم البرنامج <span class="text-rose-600">*</span></label>
            <input type="text" name="name" id="name" required value="{{ $v('name') }}" class="{{ $fieldClass }}">
            @error('name')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="{{ $labelClass }}">الوصف</label>
            <textarea name="description" id="description" rows="3" class="{{ $areaClass }}">{{ $v('description') }}</textarea>
        </div>
        <div>
            <label for="reward_mode" class="{{ $labelClass }}">وضع المكافأة <span class="text-rose-600">*</span></label>
            <select name="reward_mode" id="reward_mode" required class="{{ $fieldClass }}" data-reward-mode>
                <option value="credits" @selected($rewardMode === 'credits')>رصيد حصص (موصى به)</option>
                <option value="discount" @selected($rewardMode === 'discount')>خصم / كوبون (قديم)</option>
            </select>
        </div>
    </div>
</article>

<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft" data-mode-panel="credits">
    <div class="border-b border-line px-4 py-4 sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-ticket-alt text-sm"></i></span>
            <div>
                <h3 class="text-base font-semibold text-ink">رصيد الحصص</h3>
                <p class="mt-0.5 text-xs text-muted">كم حصة تُمنح للمحيلة والمدعوّة ومتى</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
        <div>
            <label for="credit_scope" class="{{ $labelClass }}">نطاق الرصيد</label>
            <select name="credit_scope" id="credit_scope" class="{{ $fieldClass }}">
                @foreach($scopes as $key => $label)
                    <option value="{{ $key }}" @selected($v('credit_scope', 'private_lessons') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="credit_duration_days" class="{{ $labelClass }}">صلاحية الرصيد (أيام)</label>
            <input type="number" name="credit_duration_days" id="credit_duration_days" min="1" value="{{ $v('credit_duration_days', 90) }}" class="{{ $fieldClass }}" placeholder="فارغ = بلا انتهاء">
            <p class="mt-1 text-xs text-muted">اتركه فارغاً لرصيد بلا تاريخ انتهاء</p>
        </div>
        <div>
            <label for="referred_credit_units" class="{{ $labelClass }}">حصص المدعوّة</label>
            <input type="number" name="referred_credit_units" id="referred_credit_units" min="0" value="{{ $v('referred_credit_units', 1) }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="referrer_credit_units" class="{{ $labelClass }}">حصص المحيلة</label>
            <input type="number" name="referrer_credit_units" id="referrer_credit_units" min="0" value="{{ $v('referrer_credit_units', 1) }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="grant_referred_on" class="{{ $labelClass }}">متى تُمنح المدعوّة</label>
            <select name="grant_referred_on" id="grant_referred_on" class="{{ $fieldClass }}">
                <option value="first_purchase" @selected($v('grant_referred_on', 'first_purchase') === 'first_purchase')>عند أول اشتراك مدفوع</option>
                <option value="signup" @selected($v('grant_referred_on') === 'signup')>عند التسجيل</option>
                <option value="both" @selected($v('grant_referred_on') === 'both')>تسجيل + أول اشتراك</option>
                <option value="none" @selected($v('grant_referred_on') === 'none')>بدون منح</option>
            </select>
        </div>
        <div>
            <label for="grant_referrer_on" class="{{ $labelClass }}">متى تُمنح المحيلة</label>
            <select name="grant_referrer_on" id="grant_referrer_on" class="{{ $fieldClass }}">
                <option value="first_purchase" @selected($v('grant_referrer_on', 'first_purchase') === 'first_purchase')>عند أول اشتراك للمدعوّة</option>
                <option value="none" @selected($v('grant_referrer_on') === 'none')>بدون منح</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="share_message_ar" class="{{ $labelClass }}">رسالة واتساب (عربي)</label>
            <textarea name="share_message_ar" id="share_message_ar" rows="2" class="{{ $areaClass }}" placeholder="سجّل من رابطي في حصتك واحصل على رصيد حصص: {link}">{{ $v('share_message_ar') }}</textarea>
            <p class="mt-1 text-xs text-muted">المتغيّرات: {link} · {code} · {units}</p>
        </div>
        <div class="sm:col-span-2">
            <label for="share_message_en" class="{{ $labelClass }}">WhatsApp message (EN)</label>
            <textarea name="share_message_en" id="share_message_en" rows="2" class="{{ $areaClass }}" dir="ltr" placeholder="Join Hesetak with my link: {link}">{{ $v('share_message_en') }}</textarea>
            <p class="mt-1 text-xs text-muted">إن تُركت فارغة تُنسخ تلقائياً من العربية عند الحفظ (أو قالب إنجليزي افتراضي).</p>
        </div>
    </div>
</article>

<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft" data-mode-panel="discount" @if($rewardMode !== 'discount') style="display:none" @endif>
    <div class="border-b border-line px-4 py-4 sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-tag text-sm"></i></span>
            <div>
                <h3 class="text-base font-semibold text-ink">خصم المحال (وضع قديم)</h3>
                <p class="mt-0.5 text-xs text-muted">يُستخدم فقط إذا اخترت وضع الخصم</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
        <div>
            <label for="discount_type" class="{{ $labelClass }}">نوع الخصم</label>
            <select name="discount_type" id="discount_type" class="{{ $fieldClass }}">
                <option value="percentage" @selected($v('discount_type', 'percentage') === 'percentage')>نسبة مئوية</option>
                <option value="fixed" @selected($v('discount_type') === 'fixed')>مبلغ ثابت</option>
            </select>
        </div>
        <div>
            <label for="discount_value" class="{{ $labelClass }}">قيمة الخصم</label>
            <input type="number" name="discount_value" id="discount_value" step="0.01" min="0" value="{{ $v('discount_value', 0) }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="maximum_discount" class="{{ $labelClass }}">الحد الأقصى للخصم</label>
            <input type="number" name="maximum_discount" id="maximum_discount" step="0.01" min="0" value="{{ $v('maximum_discount') }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="minimum_order_amount" class="{{ $labelClass }}">الحد الأدنى لمبلغ الطلب</label>
            <input type="number" name="minimum_order_amount" id="minimum_order_amount" step="0.01" min="0" value="{{ $v('minimum_order_amount') }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="referrer_reward_type" class="{{ $labelClass }}">نوع مكافأة المحيل (قديم)</label>
            <select name="referrer_reward_type" id="referrer_reward_type" class="{{ $fieldClass }}">
                <option value="fixed" @selected($v('referrer_reward_type', 'fixed') === 'fixed')>مبلغ ثابت</option>
                <option value="percentage" @selected($v('referrer_reward_type') === 'percentage')>نسبة مئوية</option>
                <option value="points" @selected($v('referrer_reward_type') === 'points')>نقاط</option>
            </select>
        </div>
        <div>
            <label for="referrer_reward_value" class="{{ $labelClass }}">قيمة المكافأة (قديم)</label>
            <input type="number" name="referrer_reward_value" id="referrer_reward_value" step="0.01" min="0" value="{{ $v('referrer_reward_value') }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="discount_valid_days" class="{{ $labelClass }}">صلاحية كوبون الخصم (أيام)</label>
            <input type="number" name="discount_valid_days" id="discount_valid_days" min="1" value="{{ $v('discount_valid_days', 30) }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label for="max_discount_uses_per_referred" class="{{ $labelClass }}">مرات استخدام الخصم للمحال</label>
            <input type="number" name="max_discount_uses_per_referred" id="max_discount_uses_per_referred" min="1" value="{{ $v('max_discount_uses_per_referred', 1) }}" class="{{ $fieldClass }}">
        </div>
    </div>
</article>

<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
    <div class="border-b border-line px-4 py-4 sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-cog text-sm"></i></span>
            <div>
                <h3 class="text-base font-semibold text-ink">حدود عامة</h3>
                <p class="mt-0.5 text-xs text-muted">سقف الإحالات وتواريخ البرنامج — افتراضياً محدود للحماية من الإساءة</p>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
        <div>
            <label for="max_referrals_per_user" class="{{ $labelClass }}">الحد الأقصى للإحالات لكل مستخدم</label>
            <input type="number" name="max_referrals_per_user" id="max_referrals_per_user" min="1" max="10000"
                   value="{{ $v('max_referrals_per_user', \App\Models\ReferralProgram::DEFAULT_MAX_REFERRALS_PER_USER) }}"
                   class="{{ $fieldClass }}" data-referral-limit>
            <p class="mt-1 text-xs text-muted">الافتراضي الآمن: {{ \App\Models\ReferralProgram::DEFAULT_MAX_REFERRALS_PER_USER }}. اتركه فارغاً فقط إذا أكّدتم «غير محدود» أدناه.</p>
            @error('max_referrals_per_user')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="referral_code_valid_days" class="{{ $labelClass }}">صلاحية رابط الدعوة (أيام)</label>
            <input type="number" name="referral_code_valid_days" id="referral_code_valid_days" min="1" max="3650" value="{{ $v('referral_code_valid_days') }}" class="{{ $fieldClass }}" placeholder="اختياري — ملاحظة تشغيلية">
            <p class="mt-1 text-xs text-muted">ملاحظة للأدمن/الطالب؛ لا تلغي البرنامج تلقائياً إن تُركت فارغة.</p>
        </div>
        <div>
            <label for="starts_at" class="{{ $labelClass }}">تاريخ البدء</label>
            <input type="date" name="starts_at" id="starts_at" value="{{ $v('starts_at') instanceof \Carbon\Carbon ? $v('starts_at')->format('Y-m-d') : $v('starts_at') }}" class="{{ $fieldClass }}" data-referral-starts>
        </div>
        <div>
            <label for="expires_at" class="{{ $labelClass }}">تاريخ الانتهاء</label>
            <input type="date" name="expires_at" id="expires_at" value="{{ $v('expires_at') instanceof \Carbon\Carbon ? $v('expires_at')->format('Y-m-d') : $v('expires_at') }}" class="{{ $fieldClass }}" data-referral-expires>
            @error('expires_at')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2 space-y-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="allow_unlimited_referrals" id="allow_unlimited_referrals" value="1"
                       @checked(old('allow_unlimited_referrals'))
                       class="mt-1 size-4 rounded border-line text-accent focus:ring-accent/20" data-unlimited-referrals>
                <span>
                    <span class="font-semibold">أؤكّد ترك الحد «غير محدود»</span>
                    <span class="mt-0.5 block text-xs text-amber-900/80">خطر إساءة استخدام مباشر — كل إحالة قد تمنح رصيداً مجانياً بلا سقف. لا تفعّلوا إلا بقرار واعٍ.</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="confirm_no_expiry" id="confirm_no_expiry" value="1"
                       @checked(old('confirm_no_expiry', filled($v('expires_at')) ? false : false))
                       class="mt-1 size-4 rounded border-line text-accent focus:ring-accent/20" data-confirm-no-expiry>
                <span>
                    <span class="font-semibold">أؤكّد أن البرنامج بلا تاريخ انتهاء</span>
                    <span class="mt-0.5 block text-xs text-amber-900/80">للبرامج الموسمية حدّدوا تاريخ انتهاء. بدون انتهاء يبقى البرنامج صالحاً طالما هو نشط.</span>
                </span>
            </label>
        </div>
        <div class="sm:col-span-2 rounded-xl border border-line bg-canvas px-4 py-3 text-sm text-ink">
            <p class="text-xs font-semibold text-muted">الإحالة الذاتية</p>
            <p class="mt-1 font-medium">ممنوعة دائماً (محميّة من الإساءة). لا يمكن تفعيل إحالة الحساب لنفسه أو لنفس الجوال/الجهاز.</p>
            <input type="hidden" name="allow_self_referral" value="0">
        </div>
    </div>
</article>

<article class="overflow-hidden rounded-2xl border border-accent/20 bg-accent-soft shadow-soft" data-rules-summary-card>
    <div class="px-4 py-4 sm:px-5">
        <p class="text-xs font-semibold text-accent">ملخص القاعدة قبل الحفظ</p>
        <p class="mt-1 text-sm leading-7 text-ink" data-rules-summary>…</p>
    </div>
</article>

<script>
(function(){
    var sel = document.querySelector('[data-reward-mode]');
    if (sel) {
        function syncMode(){
            var mode = sel.value;
            document.querySelectorAll('[data-mode-panel]').forEach(function(el){
                el.style.display = el.getAttribute('data-mode-panel') === mode ? '' : 'none';
            });
            refreshSummary();
        }
        sel.addEventListener('change', syncMode);
        syncMode();
    }

    var limitInput = document.querySelector('[data-referral-limit]');
    var unlimited = document.querySelector('[data-unlimited-referrals]');
    var expires = document.querySelector('[data-referral-expires]');
    var noExpiry = document.querySelector('[data-confirm-no-expiry]');
    var summary = document.querySelector('[data-rules-summary]');
    var referredUnits = document.getElementById('referred_credit_units');
    var referrerUnits = document.getElementById('referrer_credit_units');
    var shareEn = document.getElementById('share_message_en');
    var shareAr = document.getElementById('share_message_ar');

    function refreshSummary(){
        if (!summary) return;
        var mode = sel ? sel.value : 'credits';
        var maxVal = limitInput && limitInput.value !== '' ? limitInput.value : null;
        if (!maxVal && unlimited && unlimited.checked) maxVal = 'غير محدود';
        if (!maxVal) maxVal = String({{ (int) \App\Models\ReferralProgram::DEFAULT_MAX_REFERRALS_PER_USER }});
        var end = expires && expires.value ? ('ينتهي ' + expires.value) : ((noExpiry && noExpiry.checked) ? 'بلا تاريخ انتهاء (مؤكَّد)' : 'بلا تاريخ انتهاء — يلزم التأكيد');
        var ru = referredUnits ? (referredUnits.value || '1') : '1';
        var rr = referrerUnits ? (referrerUnits.value || '1') : '1';
        if (mode === 'credits') {
            summary.textContent = 'رصيد حصص: المدعو ' + ru + ' · المحيل ' + rr + ' — حد ' + maxVal + ' إحالة/مستخدم — ' + end + ' — إحالة ذاتية ممنوعة.';
        } else {
            summary.textContent = 'وضع خصم قديم — حد ' + maxVal + ' إحالة/مستخدم — ' + end + ' — إحالة ذاتية ممنوعة.';
        }
    }

    [limitInput, unlimited, expires, noExpiry, referredUnits, referrerUnits, sel].forEach(function(el){
        if (!el) return;
        el.addEventListener('input', refreshSummary);
        el.addEventListener('change', refreshSummary);
    });

    if (shareAr && shareEn) {
        shareAr.addEventListener('blur', function(){
            if ((shareEn.value || '').trim() === '' && (shareAr.value || '').trim() !== '') {
                shareEn.value = shareAr.value;
            }
        });
    }

    refreshSummary();
})();
</script>
