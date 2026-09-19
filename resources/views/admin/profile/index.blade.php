@extends('layouts.admin')

@section('title', 'الملف الشخصي - حصتك')
@section('page_title', 'الملف الشخصي')

@section('content')
@php
    $roleLabels = [
        'admin' => 'إداري',
        'super_admin' => 'مدير عام',
    ];
    $roleLabel = $roleLabels[$user->role] ?? 'إداري';
    $memberSince = $user->created_at ? $user->created_at->copy()->locale('ar')->translatedFormat('d F Y') : '—';
    $lastLogin = $user->last_login_at ? $user->last_login_at->copy()->locale('ar')->diffForHumans() : '—';
    $inputClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-2 block text-sm font-medium text-ink';
@endphp

<div class="space-y-5">
    @if(session('recovery_codes'))
        <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <div class="mb-3 flex items-center gap-3">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal"><i class="fas fa-key text-sm"></i></span>
                <div>
                    <h3 class="text-sm font-semibold text-ink">رموز الاسترداد — احفظها في مكان آمن</h3>
                    <p class="mt-0.5 text-xs text-muted">كل رمز يُستخدم مرة واحدة فقط عند فقدان جهاز المصادقة.</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                @foreach(session('recovery_codes') as $code)
                    <span class="rounded-xl border border-line bg-[#f7f8fa] px-3 py-2 text-ink">{{ $code }}</span>
                @endforeach
            </div>
            @php session()->forget('recovery_codes'); @endphp
        </div>
    @endif

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">حسابك · البيانات والأمان</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الملف الشخصي</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                <i class="fas fa-arrow-right text-xs"></i>
                لوحة التحكم
            </a>
            @if(! $user->hasTwoFactorEnabled())
                <a href="{{ route('two-factor.setup') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-shield-alt text-xs"></i>
                    تفعيل 2FA
                </a>
            @endif
        </div>
    </section>

    {{-- ملخص الحساب — كروت KPI بنفس أسلوب الداشبورد --}}
    <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-col gap-5 border-b border-line px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex items-center gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-accent-soft text-xl font-semibold text-accent">
                    @if($user->profile_image)
                        <img src="{{ $user->profile_image_url }}" alt="" class="size-full object-cover" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
                        <span class="hidden">{{ mb_substr($user->name, 0, 1) }}</span>
                    @else
                        <span>{{ mb_substr($user->name, 0, 1) }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="truncate text-base font-semibold text-ink">{{ $user->name }}</h3>
                        <span class="rounded-lg bg-accent-soft px-2 py-0.5 text-[10px] font-medium text-accent">{{ $roleLabel }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-canvas-muted px-2 py-0.5 text-[10px] font-medium {{ $user->is_active ? 'text-success' : 'text-danger' }}">
                            <span class="size-1.5 rounded-full {{ $user->is_active ? 'bg-success' : 'bg-danger' }}"></span>
                            {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-muted">
                        @if($user->email){{ $user->email }}@endif
                        @if($user->email && $user->phone) · @endif
                        @if($user->phone){{ $user->phone }}@endif
                    </p>
                </div>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-line bg-[#f7f8fa] px-3 py-1.5 text-xs font-medium text-muted">
                <i class="fas fa-lock text-accent text-[10px]"></i>
                بياناتك مشفرة وآمنة
            </span>
        </div>
        <div class="admin-kpi-grid grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
            <article class="rounded-2xl border border-line bg-[#f7f8fa] p-4">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                    <i class="fas fa-calendar-alt text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">تاريخ الانضمام</p>
                <p class="mt-1 text-sm font-semibold tracking-tight text-ink">{{ $memberSince }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-[#f7f8fa] p-4">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                    <i class="fas fa-id-badge text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">رقم العضوية</p>
                <p class="mt-1 text-sm font-semibold tabular-nums tracking-tight text-ink">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-[#f7f8fa] p-4">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">آخر تسجيل دخول</p>
                <p class="mt-1 text-sm font-semibold tracking-tight text-ink">{{ $lastLogin }}</p>
            </article>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="mb-4 flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-info-circle text-sm"></i></span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">معلومات الحساب</h3>
                        <p class="mt-0.5 text-xs text-muted">ملخص سريع لحالة حسابك</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-[#f7f8fa] px-3 py-2.5">
                        <span class="text-muted">نوع الحساب</span>
                        <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">{{ $roleLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-[#f7f8fa] px-3 py-2.5">
                        <span class="text-muted">الحالة</span>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold {{ $user->is_active ? 'text-success' : 'text-danger' }}">
                            <span class="size-2 rounded-full {{ $user->is_active ? 'bg-success' : 'bg-danger' }}"></span>
                            {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-[#f7f8fa] px-3 py-2.5">
                        <span class="text-muted">المصادقة الثنائية</span>
                        <span class="text-xs font-semibold {{ $user->hasTwoFactorEnabled() ? 'text-success' : 'text-muted' }}">
                            {{ $user->hasTwoFactorEnabled() ? 'مفعّلة' : 'غير مفعّلة' }}
                        </span>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="mb-4 flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-shield-alt text-sm"></i></span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">المصادقة الثنائية</h3>
                        <p class="mt-0.5 text-xs text-muted">طبقة أمان إضافية لتسجيل الدخول</p>
                    </div>
                </div>
                @if($user->hasTwoFactorEnabled())
                    <p class="mb-4 text-sm leading-6 text-muted">مفعّلة — يتم طلب رمز التحقق عند كل تسجيل دخول.</p>
                    <form action="{{ route('two-factor.disable') }}" method="POST" class="space-y-3" onsubmit="return confirm('هل تريد تعطيل المصادقة الثنائية؟ ستحتاج إدخال كلمة المرور.');">
                        @csrf
                        <input type="password" name="password" required placeholder="كلمة المرور للتأكيد" class="{{ $inputClass }}">
                        @error('password')
                            <p class="text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center rounded-xl border border-danger/30 bg-danger/10 text-sm font-medium text-danger transition hover:bg-danger/15">
                            تعطيل المصادقة الثنائية
                        </button>
                    </form>
                @else
                    <p class="mb-4 text-sm leading-6 text-muted">تفعيل المصادقة الثنائية يزيد أمان دخولك للمنصة.</p>
                    <a href="{{ route('two-factor.setup') }}" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-medium text-white">
                        <i class="fas fa-mobile-alt text-xs"></i>
                        تفعيل المصادقة الثنائية
                    </a>
                @endif
            </article>
        </div>

        <div class="lg:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-ink">تحديث البيانات الأساسية</h3>
                        <p class="mt-0.5 text-xs text-muted">راجع معلوماتك وحدّثها في أي وقت</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5 p-4 sm:p-5" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="name">الاسم الكامل</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $inputClass }}">
                            @error('name')
                                <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="phone">رقم الهاتف</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" required class="{{ $inputClass }}">
                            @error('phone')
                                <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}" for="email">البريد الإلكتروني <span class="font-normal text-muted">(اختياري)</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $inputClass }}">
                            @error('email')
                                <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">صورة الملف الشخصي</label>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-line bg-[#f7f8fa]">
                                @if($user->profile_image)
                                    <img src="{{ $user->profile_image_url }}" alt="" class="size-full object-cover" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
                                    <i class="fas fa-camera hidden text-2xl text-muted"></i>
                                @else
                                    <i class="fas fa-camera text-2xl text-muted"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-[#f7f8fa] px-5 py-3 text-sm font-medium text-ink-soft transition hover:border-accent/40 hover:bg-accent-soft hover:text-accent">
                                    <i class="fas fa-upload"></i>
                                    <span>اختر صورة جديدة (PNG أو JPG)</span>
                                    <input type="file" name="profile_image" accept="image/*" class="hidden">
                                </label>
                                @error('profile_image')
                                    <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl border border-line bg-[#f7f8fa] p-4 sm:p-5">
                        <div>
                            <h4 class="text-sm font-semibold text-ink">تغيير كلمة المرور</h4>
                            <p class="mt-1 text-xs text-muted">اترك الحقول فارغة إذا لم ترغب في التغيير</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted" for="current_password">كلمة المرور الحالية</label>
                                <input id="current_password" type="password" name="current_password" class="{{ $inputClass }}">
                                @error('current_password')
                                    <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted" for="password">كلمة المرور الجديدة</label>
                                <input id="password" type="password" name="password" class="{{ $inputClass }}">
                                @error('password')
                                    <p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted" for="password_confirmation">تأكيد كلمة المرور</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" class="{{ $inputClass }}">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('admin.dashboard') }}" class="btn-press order-2 inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-surface px-5 text-sm font-medium text-ink hover:bg-canvas sm:order-1">
                            <i class="fas fa-arrow-right text-xs"></i>
                            رجوع للوحة التحكم
                        </a>
                        <button type="submit" class="btn-press order-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white sm:order-2">
                            <i class="fas fa-save text-xs"></i>
                            حفظ التعديلات
                        </button>
                    </div>
                </form>
            </article>
        </div>
    </div>
</div>
@endsection
