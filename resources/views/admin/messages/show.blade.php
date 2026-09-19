@extends('layouts.admin')

@section('title', 'تفاصيل الرسالة - حصتك')
@section('header', 'تفاصيل الرسالة')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-envelope-open-text text-primary-600 ml-3"></i>
                    {{ __('تفاصيل الرسالة') }}
                </h1>
                <p class="text-gray-600">
                    {{ __('عرض تفاصيل الرسالة المرسلة عبر منصة حصتك (بريد إلكتروني / قنوات أخرى)') }}
                </p>
            </div>
            <a href="{{ route('admin.messages.index') }}" 
               class="bg-gradient-to-l from-gray-600 to-gray-500 hover:from-gray-700 hover:to-gray-600 text-white px-5 py-2.5 rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2">
                <i class="fas fa-arrow-right"></i>
                {{ __('العودة للقائمة') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- تفاصيل الرسالة -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200">
                <div class="p-6 bg-gradient-to-r from-primary-50 to-primary-100 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-file-alt text-primary-600"></i>
                            {{ __('محتوى الرسالة') }}
                        </h3>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold shadow-md
                            @if($message->status_color === 'green') bg-gradient-to-r from-green-500 to-green-600 text-white
                            @elseif($message->status_color === 'red') bg-gradient-to-r from-red-500 to-red-600 text-white
                            @elseif($message->status_color === 'blue') bg-gradient-to-r from-blue-500 to-blue-600 text-white
                            @else bg-gradient-to-r from-gray-500 to-gray-600 text-white
                            @endif">
                            <i class="fas fa-circle text-xs ml-2"></i>
                            {{ $message->status_text }}
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <!-- معاينة الرسالة كما تظهر للمستلم -->
                    <div class="bg-gradient-to-br from-primary-50 via-primary-50 to-primary-100 rounded-xl p-6 border-2 border-primary-200 shadow-lg">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center shadow-lg flex-shrink-0">
                                <i class="fas fa-envelope text-white text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-bold text-primary-700">
                                        {{ __('منصة حصتك') }}
                                    </div>
                                    @if($message->status === 'sent')
                                        <div class="text-green-600 text-lg">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    @elseif($message->status === 'delivered')
                                        <div class="text-blue-600 text-lg">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                    @elseif($message->status === 'read')
                                        <div class="text-blue-600 text-lg">
                                            <i class="fas fa-check-double text-blue-700"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm border border-primary-100 mb-3">
                                    <div class="text-gray-900 text-base whitespace-pre-wrap leading-relaxed">
                                        {{ $message->message }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-clock"></i>
                                    <span>{{ $message->sent_at ? $message->sent_at->format('d/m/Y H:i') : __('لم يتم الإرسال') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($message->error_message)
                        <div class="mt-6 p-5 bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-200 rounded-xl shadow-lg">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-base font-bold text-red-700 mb-2">
                                        {{ __('خطأ في الإرسال') }}:
                                    </div>
                                    <div class="text-red-600 text-sm bg-white rounded-lg p-3 border border-red-100">
                                        {{ $message->error_message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- معلومات إضافية -->
        <div class="space-y-6">
            <!-- معلومات المستلم -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-user-circle text-sky-600"></i>
                    {{ __('معلومات المستلم') }}
                </h3>
                
                @if($message->user)
                    <div class="flex items-center mb-6 p-4 bg-gradient-to-r from-sky-50 to-blue-50 rounded-xl border border-sky-100">
                        <div class="w-14 h-14 bg-gradient-to-br from-sky-500 to-sky-600 rounded-full flex items-center justify-center shadow-lg flex-shrink-0">
                            <span class="text-white font-bold text-lg">
                                {{ substr($message->user->name, 0, 1) }}
                            </span>
                        </div>
                        <div class="mr-4 flex-1">
                            <div class="text-base font-bold text-gray-900 mb-1">
                                {{ $message->user->name }}
                            </div>
                            <div class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fas fa-user-tag text-sky-600"></i>
                                <span>{{ $message->user->role === 'student' ? __('admin.student_role_label') : __('ولي أمر') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 flex items-center gap-2">
                            <i class="fas fa-phone text-sky-600"></i>
                            {{ __('رقم الهاتف') }}
                        </span>
                        <span class="text-gray-900 font-semibold">{{ $message->phone_number }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 flex items-center gap-2">
                            <i class="fas fa-tag text-sky-600"></i>
                            {{ __('نوع الرسالة') }}
                        </span>
                        <span class="text-gray-900 font-semibold">{{ $message->type }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 flex items-center gap-2">
                            <i class="fas fa-calendar-plus text-sky-600"></i>
                            {{ __('تاريخ الإنشاء') }}
                        </span>
                        <span class="text-gray-900 font-semibold">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($message->sent_at)
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
                            <span class="text-gray-600 flex items-center gap-2">
                                <i class="fas fa-paper-plane text-green-600"></i>
                                {{ __('تاريخ الإرسال') }}
                            </span>
                            <span class="text-gray-900 font-semibold">{{ $message->sent_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                    @if($message->delivered_at)
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <span class="text-gray-600 flex items-center gap-2">
                                <i class="fas fa-check-double text-blue-600"></i>
                                {{ __('تاريخ التسليم') }}
                            </span>
                            <span class="text-gray-900 font-semibold">{{ $message->delivered_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- الإجراءات -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-cogs text-primary-600"></i>
                    {{ __('الإجراءات') }}
                </h3>
                
                <div class="space-y-3">
                    {{-- زر إعادة الإرسال خاص بتكامل الواتساب القديم، تم إخفاؤه حالياً للرسائل البريدية --}}

                    <form action="{{ route('admin.messages.destroy', $message) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('{{ __('هل تريد حذف هذه الرسالة؟') }}')"
                                class="w-full bg-gradient-to-l from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white px-4 py-3 rounded-lg font-semibold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-trash"></i>
                            {{ __('حذف الرسالة') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- البيانات التقنية -->
            @if($message->response_data)
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-code text-sky-600"></i>
                        {{ __('البيانات التقنية') }}
                    </h3>
                    
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-5 border border-gray-200 shadow-inner">
                        <pre class="text-xs text-gray-700 overflow-x-auto font-mono leading-relaxed">{{ json_encode($message->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
