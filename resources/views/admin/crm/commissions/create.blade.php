@extends('layouts.admin')

@section('title', 'عمولة يدوية - CRM')
@section('page_title', 'إضافة عمولة يدوية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">CRM · العمولات</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">عمولة يدوية</h2>
        </div>
        <a href="{{ route('admin.crm.commissions.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm">رجوع</a>
    </section>

    <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft max-w-xl">
        <form method="POST" action="{{ route('admin.crm.commissions.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="{{ $labelClass }}">Lead <span class="text-danger">*</span></label>
                <select name="sales_lead_id" required class="{{ $fieldClass }}">
                    <option value="">اختر Lead</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" @selected(old('sales_lead_id') == $lead->id)>#{{ $lead->id }} — {{ $lead->name }}</option>
                    @endforeach
                </select>
                @error('sales_lead_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">المستفيد <span class="text-danger">*</span></label>
                <select name="user_id" required class="{{ $fieldClass }}">
                    <option value="">اختر الموظف</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('user_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">النوع <span class="text-danger">*</span></label>
                <select name="type" required class="{{ $fieldClass }}">
                    @foreach(\App\Models\CrmCommission::typeLabels() as $key => $label)
                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">المبلغ الأساس (ج.م)</label>
                <input type="number" step="0.01" min="0" name="base_amount_egp" value="{{ old('base_amount_egp') }}" class="{{ $fieldClass }}" dir="ltr">
            </div>

            <div>
                <label class="{{ $labelClass }}">نسبة العمولة %</label>
                <input type="number" step="0.01" min="0" max="100" name="commission_percent" value="{{ old('commission_percent') }}" class="{{ $fieldClass }}" dir="ltr">
            </div>

            <div>
                <label class="{{ $labelClass }}">مبلغ العمولة (ج.م)</label>
                <input type="number" step="0.01" min="0" name="commission_amount_egp" value="{{ old('commission_amount_egp') }}" class="{{ $fieldClass }}" dir="ltr">
                @error('commission_amount_egp')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-5 text-sm font-medium text-white">حفظ العمولة</button>
        </form>
    </article>
</div>
@endsection
