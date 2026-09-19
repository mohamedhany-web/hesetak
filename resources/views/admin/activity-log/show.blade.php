@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-950 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- الهيدر -->
        <div class="bg-white dark:bg-slate-800 shadow-lg rounded-lg mb-6 border border-transparent dark:border-slate-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-600">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">تفاصيل النشاط</h1>
                        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">عرض تفاصيل النشاط المسجل</p>
                    </div>
                    <a href="{{ route('admin.activity-log') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-arrow-right mr-2"></i>
                        العودة إلى القائمة
                    </a>
                </div>
            </div>

            <!-- تفاصيل النشاط -->
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- معلومات أساسية -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 border-b border-gray-200 dark:border-slate-600 pb-2">
                            المعلومات الأساسية
                        </h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">نوع النشاط</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-1
                                @if($activityLog->type == 'create') bg-green-100 text-green-800 dark:bg-emerald-900/40 dark:text-emerald-200
                                @elseif($activityLog->type == 'update') bg-blue-100 text-blue-800 dark:bg-sky-900/40 dark:text-sky-200
                                @elseif($activityLog->type == 'delete') bg-red-100 text-red-800 dark:bg-rose-900/40 dark:text-rose-200
                                @elseif($activityLog->type == 'login') bg-purple-100 text-purple-800 dark:bg-violet-900/40 dark:text-violet-200
                                @elseif($activityLog->type == 'logout') bg-gray-100 text-gray-800 dark:bg-slate-600 dark:text-slate-100
                                @else bg-yellow-100 text-yellow-800 dark:bg-amber-900/40 dark:text-amber-200 @endif">
                                @switch($activityLog->type)
                                    @case('create')
                                        إنشاء
                                        @break
                                    @case('update')
                                        تحديث
                                        @break
                                    @case('delete')
                                        حذف
                                        @break
                                    @case('login')
                                        تسجيل دخول
                                        @break
                                    @case('logout')
                                        تسجيل خروج
                                        @break
                                    @default
                                        {{ $activityLog->type }}
                                @endswitch
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">الوصف</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $activityLog->description }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">التاريخ والوقت</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-slate-100">
                                {{ $activityLog->created_at->format('Y-m-d H:i:s') }}
                                <span class="text-gray-500 dark:text-slate-400">
                                    ({{ $activityLog->created_at->diffForHumans() }})
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- معلومات المستخدم -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 border-b border-gray-200 dark:border-slate-600 pb-2">
                            معلومات المستخدم
                        </h3>
                        
                        @if($activityLog->user)
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 h-12 w-12">
                                    <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-slate-600 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 dark:text-slate-300"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $activityLog->user->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $activityLog->user->email }}</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">ID: {{ $activityLog->user->id }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-slate-400">مستخدم غير معروف</p>
                        @endif
                    </div>
                </div>

                <!-- معلومات النموذج -->
                @if($activityLog->model_type && $activityLog->model_id)
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">معلومات النموذج</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">نوع النموذج</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ class_basename($activityLog->model_type) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">معرف النموذج</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $activityLog->model_id }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- البيانات الإضافية -->
                @if($activityLog->data && is_array($activityLog->data) && count($activityLog->data) > 0)
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">البيانات الإضافية</h3>
                    <div class="bg-gray-50 dark:bg-slate-900/80 rounded-lg p-4 border border-gray-100 dark:border-slate-600">
                        <pre class="text-sm text-gray-800 dark:text-slate-200 whitespace-pre-wrap">{{ json_encode($activityLog->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif

                <!-- معلومات تقنية -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">معلومات تقنية</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-slate-300">عنوان IP</label>
                            <p class="text-gray-900 dark:text-slate-100">{{ $activityLog->ip_address ?? 'غير متوفر' }}</p>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-slate-300">وكيل المستخدم</label>
                            <p class="text-gray-900 dark:text-slate-100 break-all">{{ $activityLog->user_agent ?? 'غير متوفر' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection









