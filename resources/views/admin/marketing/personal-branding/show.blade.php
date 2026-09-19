@extends('layouts.admin')

@section('title', 'مراجعة الملف التعريفي - ' . ($personal_branding->user->name ?? ''))
@section('page_title', 'مراجعة الملف التعريفي')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $statusBadge = match ($personal_branding->status) {
        \App\Models\InstructorProfile::STATUS_APPROVED => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        \App\Models\InstructorProfile::STATUS_PENDING_REVIEW => 'border-amber-100 bg-amber-50 text-amber-700',
        \App\Models\InstructorProfile::STATUS_REJECTED => 'border-rose-100 bg-rose-50 text-rose-700',
        default => 'border-line bg-canvas text-muted',
    };

    $consultDefaults = \App\Models\ConsultationSetting::current();
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <nav class="flex flex-wrap items-center gap-1 text-sm text-muted">
        <a href="{{ route('admin.personal-branding.index') }}" class="font-medium text-accent hover:underline">التسويق الشخصي</a>
        <span>/</span>
        <span class="text-ink">{{ $personal_branding->user->name ?? 'مدرب' }}</span>
    </nav>

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الملف التعريفي — {{ $personal_branding->user->name }}</h2>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $statusBadge }}">
                <span class="size-1.5 rounded-full bg-current"></span>
                {{ \App\Models\InstructorProfile::statusLabel($personal_branding->status) }}
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.personal-branding.edit', $personal_branding) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-pen text-xs"></i>
                تعديل الملف
            </a>
            <form method="POST" action="{{ route('admin.personal-branding.destroy', $personal_branding) }}" class="inline"
                  onsubmit="return confirm('حذف الملف التعريفي بالكامل؟ سيُزال من الموقع ويمكن للمدرب إنشاء ملف جديد لاحقاً.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 hover:bg-rose-100">
                    <i class="fas fa-trash text-xs"></i>
                    حذف الملف
                </button>
            </form>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">بيانات المدرب</h3>
                    <p class="mt-0.5 text-xs text-muted">الصورة ومعلومات التواصل</p>
                </div>
                <div class="space-y-5 p-4 sm:p-5">
                    <div class="flex flex-wrap items-start gap-4">
                        @if($personal_branding->photo_path)
                            @php $photoPath = str_replace('\\', '/', trim($personal_branding->photo_path)); @endphp
                            <div class="relative size-28 overflow-hidden rounded-2xl border border-line bg-canvas">
                                <img src="{{ storage_asset($photoPath) }}" alt="صورة المدرب" class="size-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                <div class="absolute inset-0 hidden flex items-center justify-center bg-canvas text-muted">
                                    <i class="fas fa-user text-4xl"></i>
                                </div>
                            </div>
                        @else
                            <div class="flex size-28 items-center justify-center rounded-2xl bg-canvas text-muted">
                                <i class="fas fa-user text-4xl"></i>
                            </div>
                        @endif
                        <div class="space-y-1">
                            <p class="text-sm text-muted">البريد: <span class="font-medium text-ink">{{ $personal_branding->user->email ?? '—' }}</span></p>
                            <p class="text-sm text-muted">تاريخ التقديم: <span class="font-medium tabular-nums text-ink">{{ $personal_branding->submitted_at ? $personal_branding->submitted_at->format('Y-m-d H:i') : '—' }}</span></p>
                            @if($personal_branding->reviewed_at)
                                <p class="text-sm text-muted">تمت المراجعة: <span class="font-medium tabular-nums text-ink">{{ $personal_branding->reviewed_at->format('Y-m-d H:i') }}</span> — {{ $personal_branding->reviewedByUser->name ?? '' }}</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-muted">العنوان التعريفي</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $personal_branding->headline ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-muted">النبذة</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $personal_branding->bio ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted">الخبرات في المجال</p>
                        @if(count($personal_branding->experience_list) > 0)
                            <ul class="space-y-2">
                                @foreach($personal_branding->experience_list as $item)
                                    <li class="flex gap-2 text-sm text-ink">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">•</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-ink">{{ $personal_branding->experience ?: '—' }}</p>
                        @endif
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted">المهارات</p>
                        @if(count($personal_branding->skills_list) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($personal_branding->skills_list as $skill)
                                    <span class="inline-flex items-center rounded-xl border border-line bg-accent-soft px-3 py-1.5 text-sm font-medium text-accent">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-ink">{{ $personal_branding->skills ?: '—' }}</p>
                        @endif
                    </div>

                    @if($personal_branding->rejection_reason)
                        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4">
                            <p class="text-xs font-semibold text-rose-700">سبب الرفض</p>
                            <p class="mt-1 text-sm text-rose-900">{{ $personal_branding->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-comments text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">استشارة مدفوعة (ريال سعودي)</h3>
                            <p class="mt-0.5 text-xs text-muted">حدّد سعراً ومدة خاصة بهذا المدرب</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <p class="text-xs text-muted">
                        إن تركت حقل السعر فارغاً يُستخدم السعر الافتراضي للمنصة حالياً:
                        <strong class="tabular-nums text-ink">{{ number_format($consultDefaults->default_price, 2) }} $</strong>
                        — مدة افتراضية:
                        <strong class="tabular-nums text-ink">{{ (int) $consultDefaults->default_duration_minutes }} دقيقة</strong>.
                    </p>
                    <p class="text-xs text-muted">
                        السعر الظاهر للزوار الآن لهذا المدرب:
                        <strong class="tabular-nums text-accent">{{ number_format($personal_branding->effectiveConsultationPriceEgp(), 2) }} $</strong>
                        — المدة:
                        <strong class="tabular-nums text-ink">{{ $personal_branding->effectiveConsultationDurationMinutes() }} دقيقة</strong>
                    </p>

                    <form method="POST" action="{{ route('admin.personal-branding.consultation-pricing', $personal_branding) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label class="{{ $labelClass }}">سعر الاستشارة ($) — اختياري</label>
                            <input type="number" step="0.01" name="consultation_price_egp"
                                   value="{{ old('consultation_price_egp', $personal_branding->consultation_price_egp) }}"
                                   class="{{ $fieldClass }}" placeholder="فارغ = الافتراضي" dir="ltr">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">مدة الجلسة (دقيقة) — اختياري</label>
                            <input type="number" name="consultation_duration_minutes"
                                   value="{{ old('consultation_duration_minutes', $personal_branding->consultation_duration_minutes) }}"
                                   min="15" max="480" class="{{ $fieldClass }}" placeholder="فارغ = الافتراضي" dir="ltr">
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                            <button type="submit"
                                    class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                                <i class="fas fa-save text-xs"></i>
                                حفظ سعر الاستشارة
                            </button>
                            <p class="text-[11px] text-muted">تفعيل خدمة الاستشارات العامة من: إدارة المنصة ← استشارات المدربين.</p>
                        </div>
                    </form>
                </div>
            </article>
        </div>

        <aside class="space-y-5">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-clipboard-check text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">إجراءات المراجعة</h3>
                            <p class="mt-0.5 text-xs text-muted">الموافقة، الرفض، أو إعادة للمراجعة</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    @if($personal_branding->status === \App\Models\InstructorProfile::STATUS_PENDING_REVIEW)
                        <form method="POST" action="{{ route('admin.personal-branding.approve', $personal_branding) }}"
                              onsubmit="return confirm('تأكيد الموافقة على هذا الملف ونشره للطلاب في الموقع؟');">
                            @csrf
                            <button type="submit"
                                    class="btn-press flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                                <i class="fas fa-check text-xs"></i>
                                موافقة ونشر على الموقع
                            </button>
                        </form>

                        {{-- نموذج رفض بدون Alpine: يعمل حتى لو تعطّل أو تأخّر تحميل Alpine.js --}}
                        <form method="POST" action="{{ route('admin.personal-branding.reject', $personal_branding) }}"
                              class="space-y-3 rounded-2xl border border-rose-100 bg-rose-50/50 p-4"
                              onsubmit="return confirm('تأكيد رفض هذا الملف التعريفي؟ يمكن للمدرب تعديله وإعادة الإرسال.');">
                            @csrf
                            <p class="text-xs font-semibold text-rose-800">رفض الملف</p>
                            <div>
                                <label class="{{ $labelClass }}">سبب الرفض (اختياري)</label>
                                <textarea name="rejection_reason" rows="2" class="{{ $areaClass }}" placeholder="اكتب سبب الرفض للمدرب...">{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason')
                                    <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-rose-600 px-5 text-sm font-medium text-white hover:bg-rose-700">
                                تأكيد الرفض
                            </button>
                        </form>
                    @elseif(in_array($personal_branding->status, [\App\Models\InstructorProfile::STATUS_APPROVED, \App\Models\InstructorProfile::STATUS_REJECTED], true))
                        <form method="POST" action="{{ route('admin.personal-branding.send-back', $personal_branding) }}"
                              onsubmit="return confirm('إعادة هذا الملف إلى قيد المراجعة؟');">
                            @csrf
                            <button type="submit"
                                    class="btn-press flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100">
                                <i class="fas fa-undo text-xs"></i>
                                إعادة للمراجعة
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-muted">
                            هذا الملف ما زال <strong class="text-ink">مسودة</strong> ولم يُرسل من المدرب للمراجعة بعد. أزرار الموافقة والرفض تظهر عندما تكون الحالة <strong class="text-ink">قيد المراجعة</strong> (بعد ضغط المدرب على «إرسال للمراجعة»).
                        </p>
                    @endif
                </div>
            </article>
        </aside>
    </div>
</div>
@endsection
