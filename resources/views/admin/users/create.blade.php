@extends('layouts.admin')

@section('title', 'إضافة مستخدم جديد - ' . config('app.name'))
@section('page_title', 'إضافة مستخدم جديد')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $fromStudents = request('from') === 'students' || request('role') === 'student';
    $listRoute = ($fromStudents && Route::has('admin.students-accounts.index'))
        ? route('admin.students-accounts.index')
        : route('admin.users.index');
    $listLabel = ($fromStudents && Route::has('admin.students-accounts.index'))
        ? 'إدارة الطلاب والحسابات'
        : 'إدارة المستخدمين';
    $phoneCountries = $phoneCountries ?? config('phone_countries.countries', []);
    $defaultCountry = $defaultCountry ?? collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'));
    $defaultDialCode = (is_array($defaultCountry) && isset($defaultCountry['dial_code'])) ? $defaultCountry['dial_code'] : '+966';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الحسابات · {{ $listLabel }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إنشاء حساب مستخدم جديد</h2>
            <p class="mt-1 text-sm text-muted">أدخل بيانات المستخدم الأساسية، حدد دوره، واضبط حالة الحساب قبل الحفظ</p>
        </div>
        <a href="{{ $listRoute }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger shadow-soft">
            <p class="mb-2 font-semibold">يرجى تصحيح ما يلي:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm" class="space-y-5">
        @csrf

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">المعلومات الأساسية</h3>
                        <p class="mt-0.5 text-xs text-muted">بيانات الهوية والتواصل · تُستخدم في التنبيهات وتسجيل الدخول</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                        <div>
                            <label for="name" class="{{ $labelClass }}">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', '') }}" required maxlength="255" pattern="^[\p{Arabic}\s\p{N}]+$" title="الرجاء إدخال اسم صحيح (عربي فقط)" placeholder="أدخل الاسم الكامل" class="{{ $fieldClass }}" />
                            @error('name')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="{{ $labelClass }}">رقم الهاتف <span class="text-danger">*</span></label>
                            <div class="flex overflow-hidden rounded-xl border border-line bg-surface transition focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/20" dir="ltr">
                                <select name="country_code" id="country_code" required aria-label="كود الدولة" class="h-11 w-32 shrink-0 cursor-pointer border-0 border-l border-line bg-canvas px-3 text-sm text-ink focus:outline-none focus:ring-0 md:w-36">
                                    @if(empty($phoneCountries))
                                        <option value="+966" selected>+966 السعودية</option>
                                    @endif
                                    @foreach($phoneCountries as $c)
                                        @php
                                            $optValue = ($c['dial_code'] ?? '') === '' ? 'OTHER' : $c['dial_code'];
                                            $current = old('country_code', $defaultDialCode);
                                            $selected = ($current === ($c['dial_code'] ?? '')) || (($c['dial_code'] ?? '') === '' && $current === 'OTHER');
                                        @endphp
                                        <option value="{{ $optValue }}" {{ $selected ? 'selected' : '' }}>{{ $c['dial_code'] ?: '—' }} {{ $c['name_ar'] }}</option>
                                    @endforeach
                                </select>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone', '') }}" required placeholder="xxxxxxxx" maxlength="15" dir="ltr" aria-label="رقم الهاتف" class="h-11 min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-0" />
                            </div>
                            @error('phone')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" class="{{ $labelClass }}">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', '') }}" required maxlength="255" placeholder="example@hesetak.com" class="{{ $fieldClass }}" />
                            @error('email')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                            <p class="mt-1.5 text-xs text-muted">سيتم استخدام البريد الإلكتروني في إرسال الإشعارات والتنبيهات.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="password" class="{{ $labelClass }}">كلمة المرور <span class="text-danger">*</span></label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required minlength="8" maxlength="255" autocomplete="new-password" placeholder="أدخل كلمة مرور قوية" class="{{ $fieldClass }} pl-4 pr-10" />
                                <button type="button" onclick="togglePasswordVisibility('password')" class="absolute left-3 top-1/2 -translate-y-1/2 rounded p-1 text-muted transition hover:text-ink focus:outline-none focus:ring-2 focus:ring-accent/20">
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                            @error('password')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                            <p class="mt-1.5 text-xs text-muted">يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل.</p>
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">الدور والصلاحيات</h3>
                        <p class="mt-0.5 text-xs text-muted">حدد مستوى الوصول المسموح للمستخدم وحالة الحساب عند الإنشاء</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-3">
                        <div>
                            <label for="role" class="{{ $labelClass }}">الدور الأساسي في النظام <span class="text-danger">*</span></label>
                            <select name="role" id="role" required class="{{ $fieldClass }} cursor-pointer">
                                <option value="">اختر الدور</option>
                                <option value="super_admin" {{ old('role') == 'super_admin' ? 'selected' : '' }}>إداري كامل (Super Admin)</option>
                                <option value="instructor" {{ old('role') == 'instructor' ? 'selected' : '' }}>مدرس / معلم</option>
                                <option value="student" {{ old('role', $fromStudents ? 'student' : '') == 'student' ? 'selected' : '' }}>{{ __('admin.student_role_label') }}</option>
                            </select>
                            @error('role')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="rbac_role" class="{{ $labelClass }}">دور مخصص من الأدوار الموجودة <span class="font-normal text-muted">(اختياري)</span></label>
                            <select name="rbac_role" id="rbac_role" class="{{ $fieldClass }} cursor-pointer">
                                <option value="">بدون دور مخصص</option>
                                @foreach(($roles ?? []) as $roleModel)
                                    <option value="{{ $roleModel->id }}" {{ old('rbac_role') == $roleModel->id ? 'selected' : '' }}>
                                        {{ $roleModel->display_name }} ({{ $roleModel->name }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-[11px] text-muted">
                                يربط المستخدم بأحد الأدوار المعرفة في النظام وتحدد صلاحياته ما يظهر له في السايدبار داخل لوحة الأدمن.
                            </p>
                        </div>
                        <div>
                            <label for="is_active" class="{{ $labelClass }}">حالة الحساب <span class="text-danger">*</span></label>
                            <select name="is_active" id="is_active" required class="{{ $fieldClass }} cursor-pointer">
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>نشط</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>غير نشط</option>
                            </select>
                            @error('is_active')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="border-t border-line px-4 py-4 sm:px-5">
                        <div class="rounded-xl border border-line bg-canvas/60 p-4 text-sm text-ink">
                            <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-ink">
                                <i class="fas fa-info-circle text-accent"></i>
                                وصف سريع للأدوار
                            </h4>
                            <ul class="space-y-2.5 text-xs text-ink-soft">
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-shield-alt mt-0.5 shrink-0 text-accent"></i>
                                    <div><strong class="text-ink">إداري:</strong> صلاحيات كاملة لإدارة المنصة والمستخدمين.</div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-chalkboard-teacher mt-0.5 shrink-0 text-metal"></i>
                                    <div><strong class="text-ink">مدرس:</strong> إدارة المحتوى التعليمي، المحاضرات، الامتحانات.</div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-graduation-cap mt-0.5 shrink-0 text-accent"></i>
                                    <div><strong class="text-ink">{{ __('admin.student_role_label') }}:</strong> الوصول للكورسات، أداء الواجبات والامتحانات.</div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">معلومات إضافية</h3>
                        <p class="mt-0.5 text-xs text-muted">اختياري · الحد الأقصى 1000 حرف</p>
                    </div>
                    <div class="p-4 sm:p-5">
                        <label for="bio" class="{{ $labelClass }}">نبذة تعريفية <span class="font-normal text-muted">(اختياري)</span></label>
                        <textarea name="bio" id="bio" rows="4" maxlength="1000" placeholder="اكتب ملخصاً عن خبرات المستخدم أو ملاحظات داخلية..." class="{{ $areaClass }} resize-none">{{ old('bio', '') }}</textarea>
                        <p class="mt-1.5 text-xs text-muted">سيتم تنقية HTML تلقائياً.</p>
                        @error('bio')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
                    </div>
                </article>
            </div>

            <div class="space-y-5">
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">إرشادات إنشاء الحساب</h3>
                    </div>
                    <ul class="space-y-3 p-4 text-sm text-ink-soft sm:p-5">
                        <li class="flex items-start gap-2.5">
                            <i class="fas fa-check-circle mt-0.5 shrink-0 text-accent"></i>
                            <span>تأكد من صحة رقم الهاتف والبريد الإلكتروني لأنهما يُستخدمان في تسجيل الدخول والإشعارات.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fas fa-check-circle mt-0.5 shrink-0 text-accent"></i>
                            <span>اختر الدور المناسب بناءً على مهام المستخدم في الفريق.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fas fa-check-circle mt-0.5 shrink-0 text-accent"></i>
                            <span>يمكن تفعيل الحساب لاحقاً إذا رغبت في المراجعة أولاً.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fas fa-check-circle mt-0.5 shrink-0 text-accent"></i>
                            <span>أضف نبذة تعريفية للمعلمين لعرضها في صفحة الكورس.</span>
                        </li>
                    </ul>
                </article>

                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="space-y-2 p-4 sm:p-5">
                        <p class="mb-2 text-xs text-muted"><span class="font-semibold text-danger">*</span> الحقول المطلوبة لإكمال إنشاء الحساب.</p>
                        <button type="submit" id="submitBtn" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="fas fa-save text-xs"></i>
                            إنشاء المستخدم
                        </button>
                        <a href="{{ $listRoute }}" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-times text-xs"></i>
                            إلغاء والعودة
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // حماية من XSS - تنقية البيانات قبل الإرسال
    function sanitizeInput(input) {
        if (!input) return '';
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }

    // عرض/إخفاء كلمة المرور
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const eye = document.getElementById(fieldId + '-eye');
        if (field.type === 'password') {
            field.type = 'text';
            eye.classList.remove('fa-eye');
            eye.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            eye.classList.remove('fa-eye-slash');
            eye.classList.add('fa-eye');
        }
    }

    // التحقق من صحة رقم الهاتف (أرقام فقط، الطول يتحقق منه السيرفر حسب الدولة)
    document.getElementById('phone').addEventListener('input', function () {
        let sanitized = this.value.replace(/\D/g, '');
        this.value = sanitized;
        if (sanitized.length > 15) {
            this.value = sanitized.slice(0, 15);
        }
        this.setCustomValidity(sanitized.length && sanitized.length < 6 ? 'رقم الهاتف قصير جداً' : '');
    });

    // التحقق من صحة الاسم (عربي فقط)
    document.getElementById('name').addEventListener('input', function () {
        const arabicPattern = /^[\u0600-\u06FF\s]+$/;
        if (this.value && !arabicPattern.test(this.value.trim())) {
            this.setCustomValidity('الاسم يجب أن يحتوي على أحرف عربية فقط');
        } else {
            this.setCustomValidity('');
        }
    });

    // التحقق من صحة البريد الإلكتروني
    document.getElementById('email').addEventListener('input', function () {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailPattern.test(this.value.trim())) {
            this.setCustomValidity('البريد الإلكتروني غير صحيح');
        } else {
            this.setCustomValidity('');
        }
    });

    // التحقق من قوة كلمة المرور
    document.getElementById('password').addEventListener('input', function () {
        if (this.value.length < 8) {
            this.setCustomValidity('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
        } else {
            this.setCustomValidity('');
        }
    });

    // منع إرسال النموذج المتكرر (Double Submit Protection)
    let formSubmitting = false;
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        if (formSubmitting) {
            e.preventDefault();
            return false;
        }
        formSubmitting = true;
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>جاري الإنشاء...</span>';
    });

</script>
@endpush
@endsection
