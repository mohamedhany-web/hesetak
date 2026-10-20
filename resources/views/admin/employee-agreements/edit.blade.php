@extends('layouts.admin')

@section('title', 'تعديل اتفاقية الموظف - ' . config('app.name'))
@section('page_title', 'تعديل اتفاقية الموظف')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الموارد البشرية · اتفاقيات الموظفين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل اتفاقية الموظف</h2>
            <p class="mt-1 text-sm text-muted">تعديل اتفاقية: {{ $employeeAgreement->title }}</p>
        </div>
        <a href="{{ route('admin.employee-agreements.show', $employeeAgreement) }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            عرض الاتفاقية
        </a>
    </section>

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                <i class="fas fa-exclamation-circle text-sm"></i>
            </span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-soft">
            <p class="mb-2 font-semibold"><i class="fas fa-exclamation-triangle ml-1"></i>يرجى تصحيح الأخطاء التالية:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.employee-agreements.update', $employeeAgreement) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الاتفاقية</h3>
                <p class="mt-0.5 text-xs text-muted">الموظف، الراتب، التواريخ، والحالة</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="employee_id">الموظف <span class="text-rose-500">*</span></label>
                    <select id="employee_id" name="employee_id" required class="{{ $fieldClass }}">
                        <option value="">اختر الموظف</option>
                        @forelse($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $employeeAgreement->employee_id) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                                @if($employee->email)
                                    ({{ $employee->email }})
                                @endif
                            </option>
                        @empty
                            <option value="" disabled>لا يوجد موظفين متاحين</option>
                        @endforelse
                    </select>
                    @error('employee_id')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="title">عنوان الاتفاقية <span class="text-rose-500">*</span></label>
                    <input id="title" type="text" name="title" value="{{ old('title', $employeeAgreement->title) }}" required class="{{ $fieldClass }}" placeholder="مثال: اتفاقية عمل مع الموظف..." />
                    @error('title')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="salary">الراتب {{ currency_unit() }} <span class="text-rose-500">*</span></label>
                    <input id="salary" type="number" name="salary" step="0.01" min="0" value="{{ old('salary', $employeeAgreement->salary) }}" required class="{{ $fieldClass }}" placeholder="مثال: 5000" />
                    <p class="mt-1 text-xs text-muted">عند الحالة «نشط» يجب أن يكون الراتب أكبر من صفر</p>
                    @error('salary')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="start_date">تاريخ البدء <span class="text-rose-500">*</span></label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $employeeAgreement->start_date->format('Y-m-d')) }}" required class="{{ $fieldClass }}" />
                    @error('start_date')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="end_date">تاريخ الانتهاء</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $employeeAgreement->end_date ? $employeeAgreement->end_date->format('Y-m-d') : '') }}" class="{{ $fieldClass }}" />
                    @error('end_date')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" required class="{{ $fieldClass }}">
                        <option value="draft" {{ old('status', $employeeAgreement->status) == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="active" {{ old('status', $employeeAgreement->status) == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="suspended" {{ old('status', $employeeAgreement->status) == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="terminated" {{ old('status', $employeeAgreement->status) == 'terminated' ? 'selected' : '' }}>منتهي</option>
                        <option value="completed" {{ old('status', $employeeAgreement->status) == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    </select>
                    @error('status')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="3" class="{{ $areaClass }}" placeholder="وصف مختصر للاتفاقية...">{{ old('description', $employeeAgreement->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="contract_terms">شروط العقد <span class="text-rose-500">*</span></label>
                    <textarea id="contract_terms" name="contract_terms" rows="8" class="{{ $areaClass }}" placeholder="شروط وأحكام العقد...">{{ old('contract_terms', $employeeAgreement->contract_terms) }}</textarea>
                    <p class="mt-1 text-xs text-muted">إلزامي عند نقل الحالة إلى نشط. أي تعديل بعد التوقيع ينشئ إصداراً جديداً.</p>
                    @error('contract_terms')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="agreement_terms">بنود الاتفاقية <span class="text-rose-500">*</span></label>
                    <textarea id="agreement_terms" name="agreement_terms" rows="8" class="{{ $areaClass }}" placeholder="بنود وأحكام الاتفاقية...">{{ old('agreement_terms', $employeeAgreement->agreement_terms) }}</textarea>
                    @error('agreement_terms')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="notes">ملاحظات</label>
                    <textarea id="notes" name="notes" rows="3" class="{{ $areaClass }}" placeholder="ملاحظات إضافية...">{{ old('notes', $employeeAgreement->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-4 py-4 sm:px-5">
                <a href="{{ route('admin.employee-agreements.show', $employeeAgreement) }}"
                   class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    إلغاء
                </a>
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
            </div>
        </article>
    </form>
</div>
@endsection
