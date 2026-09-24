@extends('layouts.admin')

@section('title', 'تسعير الحصة حسب المرحلة والمسار - حصتك')
@section('page_title', 'تسعير الحصة الموحّد')

@section('content')
@php
    $field = 'h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">الباقات المدفوعة · {{ $currency }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">سعر الحصة الموحّد (مرحلة × نوع منهج)</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">سعر باقة الواجهة = عدد الحصص × سعر الحصة لهذا الصف والمسار. اترك الخانة فارغة للحذف/الاعتماد على الصف العام أو سعر الباقة الثابت.</p>
        </div>
        <a href="{{ route('admin.service-packages.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm">باقات الحصص</a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.service-session-rates.save') }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-canvas-muted text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">المرحلة</th>
                        @foreach($tracks as $key => $meta)
                            <th class="px-4 py-3 text-start">{{ $meta['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $i = 0; @endphp
                    @foreach($matrix as $row)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-semibold text-ink whitespace-nowrap">{{ $row['label'] }}</td>
                            @foreach($tracks as $key => $meta)
                                @php
                                    $cell = $row['cells'][$key] ?? null;
                                    $yearId = $row['year']?->id;
                                @endphp
                                <td class="px-3 py-2 min-w-[8rem]">
                                    <input type="hidden" name="rates[{{ $i }}][academic_year_id]" value="{{ $yearId }}">
                                    <input type="hidden" name="rates[{{ $i }}][curriculum_type]" value="{{ $key }}">
                                    <input type="hidden" name="rates[{{ $i }}][is_active]" value="1">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="rates[{{ $i }}][price_per_session]"
                                        value="{{ old('rates.'.$i.'.price_per_session', $cell?->price_per_session) }}"
                                        placeholder="—"
                                        class="{{ $field }}"
                                    >
                                    @php $i++; @endphp
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-4 py-3">
            <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-semibold text-white">
                <i class="fas fa-save"></i> حفظ المصفوفة
            </button>
        </div>
    </form>
</div>
@endsection
