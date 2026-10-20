@extends('layouts.admin')

@section('title', 'التسويق الشخصي - ملفات المدربين')
@section('page_title', 'التسويق الشخصي (المدربين)')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $statusBadges = [
        'approved' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'pending_review' => 'border-amber-100 bg-amber-50 text-amber-700',
        'rejected' => 'border-rose-100 bg-rose-50 text-rose-700',
        'default' => 'border-line bg-canvas text-muted',
    ];

    $filterTabs = [
        ['status' => 'pending_review', 'label' => 'قيد المراجعة', 'count' => $counts['pending'], 'active' => request('status') == 'pending_review'],
        ['status' => 'approved', 'label' => 'معتمد', 'count' => $counts['approved'], 'active' => request('status') == 'approved'],
        ['status' => 'rejected', 'label' => 'مرفوض', 'count' => $counts['rejected'], 'active' => request('status') == 'rejected'],
    ];
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

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · المدربين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مراجعة ملفات المدربين التعريفية</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">الملفات المعتمدة تظهر في الصفحة الرئيسية ضمن المدربين وعند عرض كل كورس — فقط بعد اكتمال المطابقة وخلوّها من بيانات تجريبية.</p>
        </div>
    </section>

    @if(($approvedIncompleteCount ?? 0) > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-900">
                        تدقيق: {{ $approvedIncompleteCount }} ملف بحالة «معتمد» و«ناقص مطابقة» معاً
                    </p>
                    <p class="mt-1 text-xs text-amber-800/90">
                        غالباً اعتمدت قبل تفعيل بوابة المطابقة أو عبر بيانات تجريبية. أكمل الملفات أو أوقف نشرها حتى لا تظهر للعامة ناقصة.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.personal-branding.index', ['gap' => 'approved']) }}"
                       class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-amber-300 bg-white px-4 text-sm font-medium text-amber-900 hover:bg-amber-100">
                        عرض الناقصين فقط
                    </a>
                    <form method="POST" action="{{ route('admin.personal-branding.unpublish-all-incomplete') }}"
                          onsubmit="return confirm('إيقاف نشر كل المعتمدين الناقصين وإعادتهم لقيد المراجعة؟');">
                        @csrf
                        <button type="submit"
                                class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-amber-700 px-4 text-sm font-medium text-white hover:bg-amber-800">
                            إيقاف نشر الكل
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if(request('gap') === 'approved')
        <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-sm text-ink">
            عرض تدقيق: معتمد + ناقص مطابقة.
            <a href="{{ route('admin.personal-branding.index') }}" class="font-semibold text-accent hover:underline">إلغاء الفلتر</a>
        </div>
    @endif

    <section class="flex flex-wrap gap-2">
        @foreach($filterTabs as $tab)
            <a href="{{ route('admin.personal-branding.index', ['status' => $tab['status']]) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl px-4 text-sm font-medium transition {{ $tab['active'] ? 'bg-accent text-white hover:bg-[#0d4f4a]' : 'border border-line bg-surface text-ink hover:bg-accent-soft hover:text-accent' }}">
                {{ $tab['label'] }}
                <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-lg px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $tab['active'] ? 'bg-white/20 text-white' : 'bg-canvas text-muted' }}">
                    {{ $tab['count'] }}
                </span>
            </a>
        @endforeach
        <a href="{{ route('admin.personal-branding.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent {{ !request('status') ? 'ring-1 ring-accent/30' : '' }}">
            الكل
        </a>
    </section>

    <form method="GET" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-search text-sm"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-ink">البحث</h3>
                <p class="text-xs text-muted">بحث بالاسم أو البريد</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="{{ $labelClass }}">كلمة البحث</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو البريد..."
                       class="{{ $fieldClass }}">
            </div>
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="flex items-end">
                <button type="submit"
                        class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i>
                    بحث
                </button>
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">قائمة الملفات التعريفية</h3>
                <p class="text-xs text-muted">
                    <span class="font-semibold tabular-nums text-accent">{{ $profiles->total() }}</span> ملف
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المدرب</th>
                        <th class="px-4 py-3 text-start font-medium">العنوان التعريفي</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">استشارة {{ currency_unit() }}</th>
                        <th class="px-4 py-3 text-start font-medium">تاريخ التقديم</th>
                        <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($profiles as $p)
                        @php
                            $badge = $statusBadges[$p->status] ?? $statusBadges['default'];
                            $publication = \App\Support\InstructorProfilePublication::evaluate($p);
                            $matchOk = $publication['ok'];
                            $looksDemo = $publication['is_demo'];
                        @endphp
                        <tr class="hover:bg-canvas/60 {{ (! $matchOk && $p->status === 'approved') ? 'bg-amber-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                        {{ mb_substr($p->user->name ?? '—', 0, 1, 'UTF-8') }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink">{{ $p->user->name ?? '—' }}</p>
                                        <p class="truncate text-xs text-muted">{{ $p->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink">
                                <span>{{ Str::limit($p->headline ?? '—', 40) }}</span>
                                @if($looksDemo)
                                    <span class="mt-1 inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">عنوان تجريبي</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $badge }}">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        {{ \App\Models\InstructorProfile::statusLabel($p->status) }}
                                    </span>
                                    @unless($matchOk)
                                        <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-800"
                                              title="{{ implode(' · ', $publication['missing']) }}">
                                            ناقص مطابقة
                                        </span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold tabular-nums text-ink">{{ number_format($p->effectiveConsultationPriceEgp(), 2) }}</p>
                                @if($p->usesCustomConsultationPrice())
                                    <p class="text-xs font-medium text-emerald-700">سعر خاص</p>
                                @else
                                    <p class="text-xs text-muted">افتراضي</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $p->submitted_at ? $p->submitted_at->format('Y-m-d') : '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.personal-branding.show', $p) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas hover:text-accent"
                                       title="معاينة قبل النشر">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    @if($p->status === \App\Models\InstructorProfile::STATUS_PENDING_REVIEW)
                                        @if($matchOk)
                                            <form method="POST" action="{{ route('admin.personal-branding.approve', $p) }}" class="inline"
                                                  onsubmit="return confirm('تأكيد الموافقة ونشر الملف للطلاب؟');">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                                        title="موافقة ونشر">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex size-9 items-center justify-center rounded-xl border border-line bg-canvas text-muted opacity-50"
                                                  title="لا يمكن النشر — {{ implode(' · ', $publication['missing']) }}">
                                                <i class="fas fa-ban text-xs"></i>
                                            </span>
                                        @endif
                                    @endif
                                    @if($p->status === \App\Models\InstructorProfile::STATUS_APPROVED && ! $matchOk)
                                        <form method="POST" action="{{ route('admin.personal-branding.unpublish-incomplete', $p) }}" class="inline"
                                              onsubmit="return confirm('إيقاف نشر هذا الملف وإعادته لقيد المراجعة؟');">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100"
                                                    title="إيقاف النشر (ناقص مطابقة)">
                                                <i class="fas fa-pause text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.personal-branding.edit', $p) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                       title="تعديل">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.personal-branding.destroy', $p) }}" class="inline"
                                          onsubmit="return confirm('حذف الملف التعريفي بالكامل لهذا المدرب؟ سيُزال من الموقع ويمكنه إنشاء ملف جديد لاحقاً.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                title="حذف">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="inline-flex size-12 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                                        <i class="fas fa-user-tie text-lg"></i>
                                    </span>
                                    <p class="font-semibold text-ink">لا توجد ملفات تعريفية</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($profiles->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $profiles->links() }}
            </div>
        @endif
    </article>
</div>
@endsection
