@extends('layouts.employee')

@section('title', 'اتفاقياتي')
@section('header', 'اتفاقياتي')

@section('content')
<div class="space-y-6">
    @if($agreements->count() > 0)
        @foreach($agreements as $agreement)
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl transition-shadow">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-xl font-bold text-gray-900">{{ $agreement->title }}</h3>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            @if($agreement->status === 'active') bg-green-100 text-green-800
                            @elseif($agreement->status === 'suspended') bg-yellow-100 text-yellow-800
                            @elseif($agreement->status === 'terminated') bg-red-100 text-red-800
                            @elseif($agreement->status === 'completed') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @if($agreement->status === 'active') نشطة
                            @elseif($agreement->status === 'suspended') معلقة
                            @elseif($agreement->status === 'terminated') منتهية
                            @elseif($agreement->status === 'completed') مكتملة
                            @else مسودة
                            @endif
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">رقم الاتفاقية</p>
                            <p class="font-semibold text-gray-900">{{ $agreement->agreement_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">الراتب الأساسي</p>
                            <p class="font-semibold text-green-600 text-lg">{{ number_format($agreement->salary, 2) }} {{ currency_symbol() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">تاريخ البدء</p>
                            <p class="font-semibold text-gray-900">{{ $agreement->start_date->format('Y-m-d') }}</p>
                        </div>
                    </div>

                    @if($agreement->description)
                    <p class="text-gray-600 mb-4">{{ $agreement->description }}</p>
                    @endif

                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span><i class="fas fa-calendar mr-1"></i> من {{ $agreement->start_date->format('Y-m-d') }}</span>
                        @if($agreement->end_date)
                            <span><i class="fas fa-calendar-check mr-1"></i> إلى {{ $agreement->end_date->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                
                <a href="{{ route('employee.agreements.show', $agreement) }}" class="ml-4 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors whitespace-nowrap">
                    <i class="fas fa-eye mr-2"></i>عرض التفاصيل
                </a>
            </div>
        </div>
        @endforeach
    @else
        <div class="bg-white rounded-xl shadow-lg p-12 text-center border border-gray-200">
            <i class="fas fa-file-contract text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد اتفاقيات</h3>
            <p class="text-gray-600">لم يتم إنشاء أي اتفاقيات لك حتى الآن</p>
        </div>
    @endif
</div>
@endsection
