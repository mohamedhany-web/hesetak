@extends('layouts.admin')

@section('title', 'تفاصيل الإنجاز')
@section('header', 'تفاصيل الإنجاز')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $achievement->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $achievement->category ?? $achievement->type ?? '-' }}</p>
            </div>
            <div>
                <a href="{{ route('admin.achievements.edit', $achievement) }}" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-lg font-medium transition-colors mr-2">
                    تعديل
                </a>
                <a href="{{ route('admin.achievements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors">
                    رجوع
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <div class="text-center mb-6">
                    @if($achievement->icon)
                    <i class="{{ $achievement->icon }} text-6xl text-sky-600"></i>
                    @else
                    <i class="fas fa-medal text-6xl text-sky-600"></i>
                    @endif
                </div>
                <div class="space-y-3">
                    @if($achievement->description)
                    <div>
                        <span class="text-sm text-gray-600">الوصف:</span>
                        <p class="font-medium text-gray-900 mt-1">{{ $achievement->description }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-sm text-gray-600">الفئة:</span>
                        <span class="font-medium text-gray-900 mr-2">{{ $achievement->category ?? $achievement->type ?? '-' }}</span>
                    </div>
                    @if($achievement->points_reward ?? $achievement->points)
                    <div>
                        <span class="text-sm text-gray-600">النقاط:</span>
                        <span class="font-medium text-sky-600 mr-2">{{ $achievement->points_reward ?? $achievement->points }} نقاط</span>
                    </div>
                    @endif
                </div>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">الإحصائيات</h3>
                <div class="space-y-3">
                    <div class="bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl p-4 border border-sky-200">
                        <div class="text-sm text-gray-600 mb-1">عدد الحاصلين</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $achievement->users_count ?? $achievement->users->count() ?? 0 }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                        <div class="text-sm text-gray-600 mb-1">الحالة</div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $achievement->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $achievement->is_active ? 'نشط' : 'معطل' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($achievement->users && $achievement->users->count() > 0)
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">المستخدمون الحاصلون على هذا الإنجاز</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($achievement->users->take(12) as $user)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                        <i class="fas fa-user text-sky-600"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                        <div class="text-xs text-gray-600">{{ $user->pivot->earned_at->format('Y-m-d') ?? '-' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($achievement->users->count() > 12)
            <p class="text-sm text-gray-600 mt-4">و {{ $achievement->users->count() - 12 }} مستخدم آخر</p>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection

