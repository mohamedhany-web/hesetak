@extends('layouts.admin')

@section('title', 'أرصدة الطلاب - حصتك')
@section('page_title', 'أرصدة الحصص')

@section('content')
@php $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink'; @endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التجارة · التحكم بالطالب</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">أرصدة حصص الطلاب</h2>
            <p class="mt-1 text-sm text-muted">نشط: {{ $stats['active'] }} · وحدات متبقية: {{ $stats['units_left'] }}</p>
        </div>
        <a href="{{ route('admin.student-entitlements.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">منح رصيد</a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <form method="GET" class="grid gap-3 rounded-2xl border border-line bg-surface p-4 sm:grid-cols-4">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="بحث طالب…" class="{{ $fieldClass }}">
        <select name="status" class="{{ $fieldClass }}">
            <option value="">كل الحالات</option>
            @foreach(['active'=>'نشط','expired'=>'منتهٍ','cancelled'=>'ملغى'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="scope" class="{{ $fieldClass }}">
            <option value="">كل النطاقات</option>
            @foreach(\App\Models\ServicePackage::scopes() as $k=>$v)
                <option value="{{ $k }}" @selected(request('scope')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn-press h-11 rounded-xl bg-accent text-sm font-medium text-white">تصفية</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-canvas-muted text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">الطالب</th>
                    <th class="px-4 py-3 text-start">النطاق</th>
                    <th class="px-4 py-3 text-start">الرصيد</th>
                    <th class="px-4 py-3 text-start">محجوز / قابل للحجز</th>
                    <th class="px-4 py-3 text-start">الصلاحية</th>
                    <th class="px-4 py-3 text-start">الحالة</th>
                    <th class="px-4 py-3 text-start">تعديل</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entitlements as $ent)
                    <tr class="border-t border-line">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-ink">{{ $ent->user?->name }}</div>
                            <div class="text-xs text-muted">{{ $ent->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ \App\Models\ServicePackage::scopes()[$ent->scope] ?? $ent->scope }}
                            @if($ent->academicYear || $ent->academicSubject)
                                <div class="text-xs text-muted">
                                    {{ collect([$ent->academicYear?->name, $ent->academicSubject?->name])->filter()->implode(' · ') }}
                                </div>
                            @endif
                            @if($ent->tutoringGroup)<div class="text-xs text-muted">{{ $ent->tutoringGroup->title }}</div>@endif
                            @if($ent->servicePackage)<div class="text-xs text-muted">{{ $ent->servicePackage->name }}</div>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $ent->unitsLeft() }} / {{ $ent->units_total }}</div>
                            @if($ent->order)<a href="{{ route('admin.orders.show', $ent->order) }}" class="text-[11px] font-medium text-accent">طلب #{{ $ent->order_id }}</a>@else<div class="text-[11px] text-muted">منح يدوي</div>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-ink">{{ $ent->reserved_units_count }} / {{ \App\Services\StudentEntitlementService::bookableUnitsLeft($ent) }}</div>
                            <a href="{{ route('admin.placement.create', ['entitlement_id' => $ent->id, 'student_id' => $ent->user_id, 'mode' => in_array($ent->scope, [\App\Models\ServicePackage::SCOPE_PRIVATE_LESSONS, \App\Models\ServicePackage::SCOPE_GLOBAL], true) ? 'private' : 'group']) }}" class="text-[11px] font-medium text-accent">تسكين حصة</a>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $ent->expires_at?->format('Y-m-d') ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $ent->status }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.student-entitlements.adjust', $ent) }}" class="flex flex-wrap items-center gap-1">
                                @csrf
                                <input type="number" name="units" value="1" min="1" class="h-8 w-16 rounded-lg border border-line px-2 text-xs">
                                <button name="action" value="add" class="rounded-lg bg-accent-soft px-2 py-1 text-xs text-accent">+</button>
                                <button name="action" value="subtract" class="rounded-lg bg-canvas-muted px-2 py-1 text-xs">−</button>
                                <button name="action" value="cancel" class="rounded-lg text-xs text-danger" onclick="return confirm('إلغاء؟')">إلغاء</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted">لا توجد أرصدة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $entitlements->links() }}</div>
</div>
@endsection
