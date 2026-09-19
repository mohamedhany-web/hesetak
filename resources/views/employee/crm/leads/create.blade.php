@extends('layouts.employee')

@section('title', 'إضافة عميل محتمل')
@section('header', 'إضافة عميل محتمل')

@section('content')
<div class="w-full space-y-6">
    @include('partials.crm-employee-nav', ['role' => 'marketing'])

    {{-- هيدر الصفحة (عرض الصفحة كاملاً) --}}
    <div class="rounded-2xl bg-white dark:bg-slate-800/95 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-6">
        <nav class="text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="{{ route('employee.crm.dashboard') }}" class="hover:text-teal-600 transition-colors">CRM حصتك</a>
            <span class="mx-2">/</span>
            <a href="{{ route('employee.crm.leads.index') }}" class="hover:text-teal-600 transition-colors">العملاء المحتملون</a>
            <span class="mx-2">/</span>
            <span class="text-slate-700 dark:text-slate-300 font-semibold">إضافة عميل جديد</span>
        </nav>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/50 dark:text-teal-300 flex items-center justify-center shrink-0">
                    <i class="fas fa-user-plus text-lg"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-slate-100">إضافة عميل محتمل</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">يُسجَّل باسمك كـ «مسوق بالعمولة» — ثم أرسله لصندوق المبيعات وتابع من اشترك وفي أي كورس.</p>
                </div>
            </div>
            <a href="{{ route('employee.crm.leads.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors shrink-0">
                <i class="fas fa-arrow-right"></i>
                العودة للقائمة
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/40 dark:border-teal-800 dark:text-teal-100">
        <p class="font-bold">تذكير للتسويق</p>
        <p class="mt-1 text-teal-800 dark:text-teal-200">أنت مالك التسويق لهذا العميل — عمولتك تبقى لك حتى لو تغيّر موظف المبيعات لاحقاً. ما لم يُسجَّل في CRM، لم يحدث.</p>
    </div>

    {{-- بطاقة النموذج --}}
    <div class="rounded-2xl bg-white dark:bg-slate-800/95 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('employee.crm.leads.store') }}">
            @csrf
            <div class="p-6 sm:p-8 space-y-8">
                <div class="space-y-6">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 border-b border-slate-200 dark:border-slate-700 pb-2">بيانات العميل</h2>

                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الاسم <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100"
                               placeholder="اسم العميل الكامل">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">البريد الإلكتروني</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100"
                                   placeholder="example@email.com">
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الهاتف</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100"
                                   placeholder="01xxxxxxxxx">
                            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الشركة / المؤسسة</label>
                        <input type="text" name="company" id="company" value="{{ old('company') }}"
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100"
                               placeholder="اختياري">
                        @error('company')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 border-b border-slate-200 dark:border-slate-700 pb-2">الاهتمام والمصدر</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="source" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">من أين عرفناه؟ <span class="text-red-500">*</span></label>
                            <select name="source" id="source" required
                                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100">
                                @foreach(\App\Models\SalesLead::sourceLabels() as $val => $label)
                                    <option value="{{ $val }}" {{ old('source', \App\Models\SalesLead::SOURCE_OTHER) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('source')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="interested_advanced_course_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الكورس الذي يهتم به</label>
                            <select name="interested_advanced_course_id" id="interested_advanced_course_id"
                                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100">
                                <option value="">— اختياري —</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ (string) old('interested_advanced_course_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                                @endforeach
                            </select>
                            @error('interested_advanced_course_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">ملاحظات</label>
                        <textarea name="notes" id="notes" rows="4"
                                  class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-slate-800 dark:text-slate-100"
                                  placeholder="أي تفاصيل من أول تواصل: الاهتمام، الموعد، طريقة التواصل...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-3 max-w-lg">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 font-semibold">
                            <input type="checkbox" name="submit_to_sales" value="1" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" {{ old('submit_to_sales') ? 'checked' : '' }}>
                            إرسال فوراً لصندوق المبيعات بعد الحفظ
                        </label>
                        <div class="bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl p-4 text-sm text-sky-800 dark:text-sky-200">
                            <span class="font-semibold">نصيحة:</span> تابع كل تقدمك وعمولاتك من <a href="{{ route('employee.crm.marketing.desk') }}" class="font-bold underline">لوحة المسوق بالعمولة</a>.
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 shrink-0">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold transition-colors">
                            <i class="fas fa-save"></i>
                            حفظ العميل المحتمل
                        </button>
                        <a href="{{ route('employee.crm.leads.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl font-semibold transition-colors">
                            إلغاء
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
