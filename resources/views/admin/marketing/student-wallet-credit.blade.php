@extends('layouts.admin')

@section('title', 'رصيد محفظة الطالب')
@section('header', '')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-10">
    {{-- شريط علوي --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.coupons.index') }}"
               class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
               title="العودة للكوبونات">
                <i class="fas fa-arrow-right"></i>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-heading tracking-tight">
                    إضافة رصيد لمحفظة طالب
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 max-w-xl leading-relaxed">
                    ابحث عن الطالب ثم أدخل المبلغ. يُستخدم الرصيد كخصم عند شراء الكورسات من صفحة الدفع (مع الكوبونات عند التوفر).
                </p>
            </div>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 dark:bg-sky-900/30 text-sky-800 dark:text-sky-200 px-4 py-2 text-xs font-bold border border-sky-200/80 dark:border-sky-800">
            <i class="fas fa-wallet"></i>
            تسويق
        </span>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 px-5 py-4 flex items-start gap-3 shadow-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600">
                <i class="fas fa-check"></i>
            </span>
            <p class="text-emerald-900 dark:text-emerald-100 text-sm font-semibold leading-relaxed pt-1.5">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        {{-- عمود البحث والنتائج --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 bg-gradient-to-l from-slate-50 to-white dark:from-slate-800 dark:to-slate-800/80">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-300">
                            <i class="fas fa-search"></i>
                        </span>
                        البحث عن الطالب
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mr-11">الاسم، الهاتف، البريد، أو رقم المعرف</p>
                </div>
                <div class="p-5">
                    <form method="get" action="{{ route('admin.marketing.student-wallet-credit.create') }}" class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                            <input type="search"
                                   name="search"
                                   value="{{ $search }}"
                                   autocomplete="off"
                                   placeholder="مثال: أحمد، 010، أو رقم المعرف"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 dark:text-white pr-10 pl-4 py-3 text-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-shadow">
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold px-6 py-3 text-sm transition-colors shadow-sm shadow-sky-600/20">
                                <i class="fas fa-search text-xs"></i>
                                بحث
                            </button>
                            @if($search !== '')
                                <a href="{{ route('admin.marketing.student-wallet-credit.create') }}"
                                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-semibold px-4 py-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    مسح
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @if($search === '')
                <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-900/30 px-6 py-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 text-slate-400 shadow-inner mb-4">
                        <i class="fas fa-user-graduate text-2xl"></i>
                    </div>
                    <p class="text-slate-600 dark:text-slate-300 font-semibold text-sm">ابدأ بكتابة كلمة بحث أعلاه</p>
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-2 max-w-sm mx-auto leading-relaxed">ستظهر قائمة بالطلاب المطابقين لاختيار الطالب قبل إدخال المبلغ.</p>
                </div>
            @elseif($students->isEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 dark:bg-amber-900/15 dark:border-amber-800 px-5 py-6 flex gap-4">
                    <i class="fas fa-circle-info text-amber-600 text-xl shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-amber-900 dark:text-amber-100 text-sm">لا توجد نتائج</p>
                        <p class="text-amber-800/90 dark:text-amber-200/80 text-xs mt-1 leading-relaxed">جرّب اسمًا آخر، جزءًا من رقم الهاتف، البريد، أو معرف الطالب من صفحة المستخدمين.</p>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-2 bg-slate-50/80 dark:bg-slate-900/40">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">النتائج ({{ $students->count() }})</span>
                        @if($students->count() >= 80)
                            <span class="text-[10px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-100/80 dark:bg-amber-900/40 px-2 py-0.5 rounded-md">أول 80 نتيجة</span>
                        @endif
                    </div>
                    <ul class="max-h-[min(420px,55vh)] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700" id="student-results-list">
                        @foreach($students as $s)
                            <li>
                                <label class="flex items-center gap-4 px-4 py-3.5 cursor-pointer hover:bg-sky-50/60 dark:hover:bg-slate-700/40 transition-colors has-[:checked]:bg-sky-50 dark:has-[:checked]:bg-sky-900/20 has-[:checked]:ring-2 has-[:checked]:ring-inset has-[:checked]:ring-sky-400/50">
                                    <input type="radio"
                                           name="user_id"
                                           form="wallet-credit-store-form"
                                           value="{{ $s->id }}"
                                           class="h-4 w-4 rounded-full border-slate-300 text-sky-600 focus:ring-sky-500"
                                           @checked((string) old('user_id', $selectedStudent?->id) === (string) $s->id)>
                                    <div class="min-w-0 flex-1 text-start">
                                        <p class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ $s->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5">
                                            @if($s->phone)
                                                <span dir="ltr" class="inline-flex items-center gap-1"><i class="fas fa-phone text-[10px] opacity-60"></i>{{ $s->phone }}</span>
                                            @endif
                                            @if($s->email)
                                                <span dir="ltr" class="inline-flex items-center gap-1 truncate max-w-[200px]"><i class="fas fa-envelope text-[10px] opacity-60"></i>{{ $s->email }}</span>
                                            @endif
                                            <span class="text-slate-400 font-mono">#{{ $s->id }}</span>
                                        </p>
                                    </div>
                                    <i class="fas fa-chevron-left text-slate-300 dark:text-slate-500 text-xs shrink-0"></i>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($selectedStudent && ($students->isEmpty() || ! $students->contains('id', $selectedStudent->id)))
                <div class="rounded-2xl border border-violet-200 bg-violet-50 dark:bg-violet-900/20 dark:border-violet-800 px-4 py-3 text-sm">
                    <p class="font-bold text-violet-900 dark:text-violet-100 text-xs mb-1 flex items-center gap-1">
                        <i class="fas fa-user-check"></i>
                        الطالب المحدد (يُرسل مع النموذج)
                    </p>
                    <p class="text-violet-900 dark:text-violet-100 font-semibold">{{ $selectedStudent->name }}</p>
                    <p class="text-violet-700/90 dark:text-violet-300/90 text-xs mt-1">معرف #{{ $selectedStudent->id }} — أعد البحث ليظهر في القائمة إن أردت تغييره.</p>
                </div>
            @endif
        </div>

        {{-- عمود النموذج --}}
        <div class="lg:col-span-5">
            <form id="wallet-credit-store-form"
                  action="{{ route('admin.marketing.student-wallet-credit.store') }}"
                  method="POST"
                  class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg shadow-slate-200/40 dark:shadow-none overflow-hidden sticky top-6">
                @csrf

                @if($selectedStudent && ($students->isEmpty() || ! $students->contains('id', $selectedStudent->id)))
                    <input type="hidden" name="user_id" id="wallet-preserved-user-id" value="{{ $selectedStudent->id }}">
                @endif

                <div class="px-5 py-4 bg-gradient-to-br from-sky-600 to-indigo-700 text-white">
                    <h2 class="text-lg font-black flex items-center gap-2">
                        <i class="fas fa-coins opacity-90"></i>
                        بيانات الإيداع
                    </h2>
                    <p class="text-sky-100 text-xs mt-1 opacity-95">المبلغ يُضاف فوراً لرصيد محفظة الطالب</p>
                </div>

                <div class="p-5 space-y-5">
                    @error('user_id')
                        <p class="text-red-600 dark:text-red-400 text-xs font-semibold flex items-center gap-1">
                            <i class="fas fa-circle-exclamation"></i>{{ $message }}
                        </p>
                    @enderror

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">
                            المبلغ {{ currency_unit() }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="amount"
                                   step="0.01"
                                   min="0.01"
                                   required
                                   value="{{ old('amount') }}"
                                   placeholder="0.00"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 dark:text-white pl-4 pr-12 py-3.5 text-lg font-bold tabular-nums focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">{{ currency_symbol() }}</span>
                        </div>
                        @error('amount')
                            <p class="text-red-600 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">ملاحظات (اختياري)</label>
                        <textarea name="notes"
                                  rows="3"
                                  class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 resize-y min-h-[88px]"
                                  placeholder="مثال: حملة رمضان، تعويض، مكافأة إحالة">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-600 dark:text-red-400 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-white font-black py-3.5 text-sm shadow-lg shadow-sky-600/25 transition-all active:scale-[0.99]">
                        <i class="fas fa-plus-circle"></i>
                        إضافة الرصيد للمحفظة
                    </button>

                    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center leading-relaxed">
                        تأكد من اختيار الطالب من نتائج البحث قبل الإرسال.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

@if($selectedStudent && $students->isNotEmpty() && ! $students->contains('id', $selectedStudent->id))
    <script>
        document.querySelectorAll('#student-results-list input[name="user_id"][type="radio"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.getElementById('wallet-preserved-user-id')?.remove();
            });
        });
    </script>
@endif
@endsection
