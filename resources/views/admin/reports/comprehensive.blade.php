@extends('layouts.admin')

@section('title', 'التقرير الشامل')
@section('header', 'التقرير الشامل')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <section class="rounded-2xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center text-white shadow-md">
                    <i class="fas fa-file-alt text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-900">التقرير الشامل</h2>
                    <p class="text-sm text-slate-600 mt-1">تقرير شامل يغطي جميع جوانب المنصة في ملف واحد</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <i class="fas fa-arrow-right"></i>
                    العودة
                </a>
                <a href="{{ route('admin.reports.export.comprehensive', array_merge(request()->all())) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-file-excel"></i>
                    تصدير إلى Excel
                </a>
            </div>
        </div>
    </section>

    <!-- الفلاتر -->
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                <i class="fas fa-filter text-blue-600"></i>
                الفلاتر
            </h3>
        </div>
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">الفترة</label>
                    <select name="period" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="today" {{ $period == 'today' ? 'selected' : '' }}>اليوم</option>
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>هذا الشهر</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>هذا العام</option>
                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>الكل</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">من تاريخ</label>
                    <input type="date" name="start_date" value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">إلى تاريخ</label>
                    <input type="date" name="end_date" value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div class="md:col-span-3 flex items-end gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-filter"></i>
                        تطبيق الفلاتر
                    </button>
                    <a href="{{ route('admin.reports.comprehensive') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </section>

    <!-- الإحصائيات الشاملة -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- المستخدمين -->
        <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-users text-blue-600"></i>
                    المستخدمين
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">إجمالي المستخدمين</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['users']['total']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">الطلاب</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['users']['students']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">المدربين</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['users']['instructors']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200">
                        <span class="text-sm font-semibold text-blue-700">جدد هذا الفترة</span>
                        <span class="text-lg font-black text-blue-700">{{ number_format($stats['users']['new_this_period']) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- الكورسات -->
        <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-graduation-cap text-emerald-600"></i>
                    الكورسات
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">إجمالي الكورسات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['courses']['total']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">الكورسات النشطة</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['courses']['active']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">إجمالي التسجيلات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['courses']['total_enrollments']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                        <span class="text-sm font-semibold text-emerald-700">التسجيلات النشطة</span>
                        <span class="text-lg font-black text-emerald-700">{{ number_format($stats['courses']['active_enrollments']) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- المالية -->
        <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-amber-600"></i>
                    المالية
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                        <span class="text-sm font-semibold text-emerald-700">إجمالي الإيرادات</span>
                        <span class="text-lg font-black text-emerald-700">{{ number_format($stats['financial']['total_revenue'], 2) }} {{ currency_symbol() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-rose-50 border border-rose-200">
                        <span class="text-sm font-semibold text-rose-700">إجمالي المصروفات</span>
                        <span class="text-lg font-black text-rose-700">{{ number_format($stats['financial']['total_expenses'], 2) }} {{ currency_symbol() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200">
                        <span class="text-sm font-semibold text-blue-700">الربح الصافي</span>
                        <span class="text-lg font-black {{ ($stats['financial']['net_profit'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($stats['financial']['net_profit'], 2) }} {{ currency_symbol() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">عدد الفواتير</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['financial']['total_invoices']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">عدد المعاملات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['financial']['total_transactions']) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- المحتوى الدراسي -->
        <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-book text-purple-600"></i>
                    المحتوى الدراسي
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">الامتحانات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['academic']['total_exams']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">المحاولات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['academic']['total_attempts']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">الواجبات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['academic']['total_assignments']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">التسليمات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['academic']['total_submissions']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                        <span class="text-sm font-semibold text-slate-700">المحاضرات</span>
                        <span class="text-lg font-black text-slate-900">{{ number_format($stats['academic']['total_lectures']) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-purple-50 border border-purple-200">
                        <span class="text-sm font-semibold text-purple-700">الشهادات</span>
                        <span class="text-lg font-black text-purple-700">{{ number_format($stats['academic']['total_certificates']) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- أخرى -->
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                معلومات أخرى
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                    <span class="text-sm font-semibold text-slate-700">الطلبات</span>
                    <span class="text-lg font-black text-slate-900">{{ number_format($stats['other']['total_orders']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                    <span class="text-sm font-semibold text-slate-700">الإشعارات</span>
                    <span class="text-lg font-black text-slate-900">{{ number_format($stats['other']['total_notifications']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                    <span class="text-sm font-semibold text-slate-700">النشاطات</span>
                    <span class="text-lg font-black text-slate-900">{{ number_format($stats['other']['total_activities']) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                    <span class="text-sm font-semibold text-slate-700">رسائل التواصل</span>
                    <span class="text-lg font-black text-slate-900">{{ number_format($stats['other']['total_contacts']) }}</span>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
