@extends('layouts.admin')

@section('title', 'إدارة الرسائل - حصتك')
@section('header', 'إدارة الرسائل')

@section('content')
@php
    $statCards = [
        [
            'label' => 'إجمالي الرسائل',
            'value' => number_format($stats['total_messages'] ?? 0),
            'icon' => 'fas fa-envelope',
            'color' => 'text-sky-500 bg-sky-100/70',
            'description' => 'كل الرسائل المسجلة',
        ],
        [
            'label' => 'رسائل اليوم',
            'value' => number_format($stats['sent_today'] ?? 0),
            'icon' => 'fas fa-paper-plane',
            'color' => 'text-emerald-500 bg-emerald-100/70',
            'description' => 'تم الإرسال اليوم',
        ],
        [
            'label' => 'رسائل فاشلة',
            'value' => number_format($stats['failed_messages'] ?? 0),
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'text-rose-500 bg-rose-100/70',
            'description' => 'تحتاج للمراجعة',
        ],
        [
            'label' => 'تقارير هذا الشهر',
            'value' => number_format($stats['monthly_reports'] ?? 0),
            'icon' => 'fas fa-chart-bar',
            'color' => 'text-purple-500 bg-purple-100/70',
            'description' => 'تقارير شهرية',
        ],
    ];
@endphp

<div class="space-y-6 sm:space-y-10">
    <!-- Header Section -->
    <section class="rounded-3xl bg-white/95 backdrop-blur border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-5 py-6 sm:px-8 lg:px-12 border-b border-slate-200 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">لوحة إدارة الرسائل في حصتك</h2>
                <p class="text-sm text-slate-500 mt-2">مركز موحد لإدارة وإرسال الرسائل والتنبيهات للطلاب والموظفين</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.messages.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-sky-600 rounded-xl shadow hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all">
                    <i class="fas fa-plus"></i>
                    <span>رسالة جديدة</span>
                </a>
                <a href="{{ route('admin.messages.monthly-reports') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-xl shadow hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all">
                    <i class="fas fa-chart-line"></i>
                    <span class="hidden sm:inline">التقارير الشهرية</span>
                </a>
                <a href="{{ route('admin.messages.templates') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-purple-600 rounded-xl shadow hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all">
                    <i class="fas fa-file-alt"></i>
                    <span class="hidden sm:inline">قوالب الرسائل</span>
                </a>
                <a href="{{ route('admin.messages.settings') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-xl shadow hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all">
                    <i class="fas fa-cog"></i>
                    <span class="hidden sm:inline">الإعدادات</span>
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 p-5 sm:p-8">
            @foreach ($statCards as $card)
                <div class="rounded-2xl border border-slate-200 bg-white/70 p-5 flex flex-col gap-4 card-hover-effect">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $card['color'] }}">
                            <i class="{{ $card['icon'] }} text-xl"></i>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">{{ $card['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Search and Filters -->
    <section class="rounded-3xl bg-white/95 backdrop-blur border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-5 py-6 sm:px-8 lg:px-12 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-filter text-sky-600"></i>
                فلترة وبحث الرسائل
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">البحث</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="البحث في الرسائل، الأسماء، أو الأرقام..."
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 pr-10 text-sm text-slate-900 focus:ring-2 focus:ring-sky-500 focus:border-sky-400 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">الحالة</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-sky-500 focus:border-sky-400 transition-all">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>تم الإرسال</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فشل الإرسال</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">النوع</label>
                    <select name="type" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-sky-500 focus:border-sky-400 transition-all">
                        <option value="">جميع الأنواع</option>
                        <option value="text" {{ request('type') === 'text' ? 'selected' : '' }}>رسالة نصية</option>
                        <option value="template" {{ request('type') === 'template' ? 'selected' : '' }}>قالب</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                        <i class="fas fa-search"></i>
                        <span>بحث</span>
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'type']))
                    <a href="{{ route('admin.messages.index') }}" 
                       class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-2xl font-semibold transition-colors"
                       title="مسح الفلتر">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Messages List -->
    <section class="rounded-3xl bg-white/95 backdrop-blur border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-5 py-6 sm:px-8 lg:px-12 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-list text-sky-600"></i>
                    سجل الرسائل
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    <span class="font-semibold text-sky-600">{{ $messages->total() }}</span> رسالة
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i class="fas fa-clock"></i>
                <span>آخر تحديث: {{ now()->format('H:i') }}</span>
            </div>
        </div>

        @if($messages->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <th class="px-6 py-4 text-right">
                                <i class="fas fa-user ml-2 text-sky-500"></i>
                                المستلم
                            </th>
                            <th class="px-6 py-4 text-right">
                                <i class="fas fa-comment ml-2 text-sky-500"></i>
                                الرسالة
                            </th>
                            <th class="px-6 py-4 text-right">
                                <i class="fas fa-info-circle ml-2 text-sky-500"></i>
                                الحالة
                            </th>
                            <th class="px-6 py-4 text-right">
                                <i class="fas fa-clock ml-2 text-sky-500"></i>
                                تاريخ الإرسال
                            </th>
                            <th class="px-6 py-4 text-center">
                                <i class="fas fa-cog ml-2 text-sky-500"></i>
                                الإجراءات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($messages as $message)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                                            <span class="text-white font-bold text-lg">
                                                {{ $message->user ? mb_substr($message->user->name, 0, 1, 'UTF-8') : 'غ' }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ $message->user->name ?? 'غير معروف' }}
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                                <i class="fas fa-phone text-slate-400"></i>
                                                <span>{{ $message->phone_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-900 max-w-md">
                                        <div class="line-clamp-2">
                                            {{ Str::limit($message->message, 120) }}
                                        </div>
                                        @if(strlen($message->message) > 120)
                                        <a href="{{ route('admin.messages.show', $message) }}" class="text-xs text-sky-600 hover:text-sky-800 mt-1 inline-block">
                                            قراءة المزيد...
                                        </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm
                                        @if($message->status_color === 'green') bg-emerald-100 text-emerald-700
                                        @elseif($message->status_color === 'red') bg-rose-100 text-rose-700
                                        @elseif($message->status_color === 'blue') bg-sky-100 text-sky-700
                                        @elseif($message->status_color === 'yellow') bg-amber-100 text-amber-700
                                        @else bg-slate-100 text-slate-700
                                        @endif">
                                        @if($message->status === 'sent')
                                            <i class="fas fa-check-circle"></i>
                                        @elseif($message->status === 'failed')
                                            <i class="fas fa-times-circle"></i>
                                        @elseif($message->status === 'delivered')
                                            <i class="fas fa-check-double"></i>
                                        @else
                                            <i class="fas fa-clock"></i>
                                        @endif
                                        <span>{{ $message->status_text }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 font-medium">
                                        {{ $message->sent_at ? $message->sent_at->format('d/m/Y') : '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $message->sent_at ? $message->sent_at->format('H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.messages.show', $message) }}" 
                                           class="w-9 h-9 flex items-center justify-center bg-sky-50 hover:bg-sky-100 text-sky-600 rounded-xl transition-colors"
                                           title="عرض التفاصيل">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        @if($message->status === 'failed')
                                            <form action="{{ route('admin.messages.resend', $message) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-9 h-9 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-xl transition-colors"
                                                        title="إعادة الإرسال"
                                                        onclick="return confirm('هل تريد إعادة إرسال هذه الرسالة؟')">
                                                    <i class="fas fa-redo text-sm"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="w-9 h-9 flex items-center justify-center bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-colors"
                                                    title="حذف"
                                                    onclick="return confirm('هل تريد حذف هذه الرسالة؟')">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($messages->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $messages->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope-open text-slate-400 text-5xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">
                    لا توجد رسائل
                </h3>
                <p class="text-slate-600 mb-6 max-w-md mx-auto">
                    ابدأ بإرسال أول رسالة للطلاب عبر الواتساب
                </p>
                <a href="{{ route('admin.messages.create') }}" 
                   class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-plus"></i>
                    <span>إرسال رسالة جديدة</span>
                </a>
            </div>
        @endif
    </section>
</div>

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
@endsection
