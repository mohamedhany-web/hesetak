@extends('layouts.admin')

@section('title', 'تسعير الباقات المخصصة - حصتك')
@section('page_title', 'تسعير الباقات المخصصة')

@section('content')
@php
    $editing = $editRule->exists;
    $action = $editing
        ? route('admin.service-package-pricing-rules.update', $editRule)
        : route('admin.service-package-pricing-rules.store');
    $tiers = old('discount_min_sessions')
        ? collect(old('discount_min_sessions'))->map(fn ($min, $i) => [
            'min_sessions' => $min,
            'discount_percent' => old('discount_percent')[$i] ?? '',
        ])->all()
        : ($editRule->discount_tiers ?? []);
    while (count($tiers) < 3) {
        $tiers[] = ['min_sessions' => '', 'discount_percent' => ''];
    }
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">الباقات والأسعار · بالريال السعودي</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">مدخلات «خصص باقتك الخاصة»</h2>
            <p class="mt-1 text-sm text-muted">قاعدة الحصص الخاصة تُستخدم في مخصص الطالب: مدة شهر أو 3 أشهر × عدد حصص أسبوعي ثابت. الإجمالي = الحصص الأسبوعية × 4 × عدد الشهور.</p>
        </div>
        <a href="{{ route('public.service-packages.index') }}" target="_blank" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm">
            <i class="fas fa-arrow-up-right-from-square text-xs"></i> معاينة الصفحة
        </a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger">
            <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(420px,.8fr)]">
        <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-3">
                <h3 class="font-semibold text-ink">قواعد التسعير الحالية</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-canvas-muted text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start">الخدمة</th>
                            <th class="px-4 py-3 text-start">سعر الحصة</th>
                            <th class="px-4 py-3 text-start">الحدود</th>
                            <th class="px-4 py-3 text-start">المدة/الصلاحية</th>
                            <th class="px-4 py-3 text-start">الخصومات</th>
                            <th class="px-4 py-3 text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-ink">{{ $rule->name }}</div>
                                    <div class="text-xs text-muted">{{ $rule->scopeLabel() }} · {{ $rule->is_active ? 'نشطة' : 'موقوفة' }}</div>
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ number_format((float) $rule->price_per_session, 2) }} {{ currency_symbol() }}</td>
                                <td class="px-4 py-3">{{ $rule->min_sessions }}–{{ $rule->max_sessions }} <span class="text-xs text-muted">/ خطوة {{ $rule->session_step }}</span></td>
                                <td class="px-4 py-3">{{ $rule->session_minutes }} د <span class="block text-xs text-muted">{{ $rule->duration_days }} يوم</span></td>
                                <td class="px-4 py-3 text-xs">
                                    @forelse($rule->discount_tiers ?? [] as $tier)
                                        <span class="mb-1 inline-flex rounded-full bg-canvas-muted px-2 py-1">{{ $tier['min_sessions'] }}+ = {{ $tier['discount_percent'] }}%</span>
                                    @empty
                                        <span class="text-muted">بدون</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.service-package-pricing-rules.index', ['edit' => $rule->id]) }}" class="text-accent hover:underline">تعديل</a>
                                        <form method="POST" action="{{ route('admin.service-package-pricing-rules.destroy', $rule) }}" onsubmit="return confirm('حذف قاعدة التسعير؟')">
                                            @csrf @method('DELETE')
                                            <button class="text-danger hover:underline">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-muted">لا توجد قواعد تسعير.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-surface p-4 shadow-soft sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-ink">{{ $editing ? 'تعديل قاعدة التسعير' : 'إضافة قاعدة تسعير' }}</h3>
                    <p class="text-xs text-muted">جميع الأسعار بالريال السعودي (ر.س).</p>
                </div>
                @if($editing)
                    <a href="{{ route('admin.service-package-pricing-rules.index') }}" class="text-sm text-accent">إلغاء</a>
                @endif
            </div>

            <form method="POST" action="{{ $action }}" class="space-y-4">
                @csrf
                @if($editing) @method('PUT') @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="name">اسم الخيار للطالب *</label>
                        <input id="name" name="name" required value="{{ old('name', $editRule->name) }}" class="{{ $field }}" placeholder="مثال: حصص فردية">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="scope">الخدمة التي يُستخدم فيها *</label>
                        <select id="scope" name="scope" class="{{ $field }}">
                            @foreach(\App\Models\ServicePackage::scopes() as $value => $scopeLabel)
                                <option value="{{ $value }}" @selected(old('scope', $editRule->scope) === $value)>{{ $scopeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="price_per_session">سعر الحصة بالريال *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center font-semibold text-muted">$</span>
                            <input id="price_per_session" type="number" step="0.01" min="0.01" name="price_per_session" required value="{{ old('price_per_session', $editRule->price_per_session) }}" class="{{ $field }} pl-8" dir="ltr">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="min_sessions">أقل عدد حصص *</label>
                        <input id="min_sessions" type="number" min="1" name="min_sessions" required value="{{ old('min_sessions', $editRule->min_sessions) }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="max_sessions">أقصى عدد حصص *</label>
                        <input id="max_sessions" type="number" min="1" name="max_sessions" required value="{{ old('max_sessions', $editRule->max_sessions) }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="session_step">الزيادة المسموحة *</label>
                        <input id="session_step" type="number" min="1" name="session_step" required value="{{ old('session_step', $editRule->session_step) }}" class="{{ $field }}">
                        <p class="mt-1 text-[11px] text-muted">مثال 2: 2، 4، 6… حصص.</p>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="session_minutes">مدة الحصة بالدقائق *</label>
                        <input id="session_minutes" type="number" min="15" max="480" name="session_minutes" required value="{{ old('session_minutes', $editRule->session_minutes) }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="duration_days">صلاحية الرصيد بالأيام *</label>
                        <input id="duration_days" type="number" min="1" max="730" name="duration_days" required value="{{ old('duration_days', $editRule->duration_days) }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="sort_order">الترتيب</label>
                        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $editRule->sort_order) }}" class="{{ $field }}">
                    </div>
                    <label class="flex items-center gap-2 self-end pb-3 text-sm text-ink">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editRule->is_active ?? true))>
                        متاحة في منشئ الباقات
                    </label>
                </div>

                <div>
                    <p class="{{ $label }}">شرائح خصم الكمية (اختياري)</p>
                    <div class="grid gap-2">
                        @foreach(array_slice($tiers, 0, 5) as $tier)
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" min="1" name="discount_min_sessions[]" value="{{ $tier['min_sessions'] ?? '' }}" class="{{ $field }}" placeholder="من عدد حصص">
                                <input type="number" min="0" max="100" step="0.01" name="discount_percent[]" value="{{ $tier['discount_percent'] ?? '' }}" class="{{ $field }}" placeholder="نسبة الخصم %">
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-1 text-[11px] text-muted">يُطبق أعلى خصم ينطبق على العدد الذي يختاره الطالب.</p>
                </div>

                <button class="btn-press inline-flex h-10 w-full items-center justify-center rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    {{ $editing ? 'حفظ التعديلات' : 'إضافة قاعدة التسعير' }}
                </button>
            </form>
        </section>
    </div>
</div>
@endsection
