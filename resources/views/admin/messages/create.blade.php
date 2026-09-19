@extends('layouts.admin')

@section('title', 'إرسال رسالة جديدة - حصتك')
@section('header', 'إرسال رسالة جديدة')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('إرسال رسالة جديدة') }}</h1>
                <p class="text-gray-600">{{ __('إرسال رسائل وتنبيهات للطلاب والموظفين عبر المنصة (واتساب / بريد إلكتروني)') }}</p>
            </div>
            <a href="{{ route('admin.messages.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-right ml-2"></i>
                {{ __('العودة') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- نموذج الإرسال -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6" x-data="{ 
                    recipientType: 'single', 
                    selectedTemplate: '',
                    message: '',
                    selectedStudents: []
                }">
                    
                    <!-- اختيار نوع المستلمين -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            {{ __('الفئة المستهدفة') }}
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <label class="relative">
                                <input type="radio" name="recipient_type" value="single" x-model="recipientType" 
                                       class="sr-only peer">
                                <div class="p-4 border border-gray-300 rounded-lg cursor-pointer 
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-user text-primary-600 ml-2"></i>
                                        <span class="text-gray-900 font-medium">{{ __('طالب واحد') }}</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative">
                                <input type="radio" name="recipient_type" value="course_students" x-model="recipientType" 
                                       class="sr-only peer">
                                <div class="p-4 border border-gray-300 rounded-lg cursor-pointer 
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-graduation-cap text-primary-600 ml-2"></i>
                                        <span class="text-gray-900 font-medium">{{ __('طلاب كورس') }}</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative">
                                <input type="radio" name="recipient_type" value="all_students" x-model="recipientType" 
                                       class="sr-only peer">
                                <div class="p-4 border border-gray-300 rounded-lg cursor-pointer 
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-users text-primary-600 ml-2"></i>
                                        <span class="text-gray-900 font-medium">{{ __('جميع الطلاب') }}</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative">
                                <input type="radio" name="recipient_type" value="all_employees" x-model="recipientType"
                                       class="sr-only peer">
                                <div class="p-4 border border-gray-300 rounded-lg cursor-pointer 
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-briefcase text-primary-600 ml-2"></i>
                                        <span class="text-gray-900 font-medium">{{ __('جميع الموظفين') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <form id="messageForm" method="POST">
                        @csrf
                        <input type="hidden" name="recipient_type" x-model="recipientType">

                        <!-- اختيار الطالب (للرسائل الفردية) -->
                        <div x-show="recipientType === 'single'" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('اختر الطالب') }}
                            </label>
                            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900">
                                <option value="">{{ __('اختر طالب...') }}</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }} - {{ $student->phone }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- اختيار الكورس (لطلاب كورس معين) -->
                        <div x-show="recipientType === 'course_students'" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('اختر الكورس') }}
                            </label>
                            <select name="course_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900">
                                <option value="">{{ __('اختر كورس...') }}</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- اختيار قناة الإرسال -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('قناة الإرسال') }}
                            </label>
                            <select name="channel" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900">
                                <option value="email">{{ __('بريد إلكتروني') }}</option>
                            </select>
                        </div>

                        <!-- اختيار قالب الرسالة -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('قالب الرسالة (اختياري)') }}
                            </label>
                            <select name="template_id" x-model="selectedTemplate" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900">
                                <option value="">{{ __('اختر قالب أو اكتب رسالة مخصصة...') }}</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" data-content="{{ $template->content }}">
                                        {{ $template->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- نص الرسالة -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('نص الرسالة') }}
                            </label>
                            <textarea name="message" rows="8" required x-model="message"
                                      placeholder="{{ __('اكتب رسالتك هنا...') }}"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                            <div class="mt-2 text-xs text-gray-500">
                                {{ __('الحد الأقصى: 4096 حرف') }}
                                <span x-text="message.length"></span>/4096
                            </div>
                        </div>

                        <!-- أزرار الإرسال -->
                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" onclick="previewMessage()" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                <i class="fas fa-eye ml-2"></i>
                                {{ __('معاينة') }}
                            </button>
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium shadow-sm transition-colors">
                                <i class="fas fa-paper-plane ml-2"></i>
                                {{ __('إرسال الرسالة') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معاينة الرسالة -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-mobile-alt ml-2"></i>
                    {{ __('معاينة الرسالة') }}
                </h3>
                
                <div class="bg-primary-50 rounded-lg p-4 border border-primary-200">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-envelope text-white"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs text-green-700 mb-1">
                                {{ __('منصة حصتك') }}
                                @isset($prefillTitle)
                                    - {{ $prefillTitle }}
                                @endisset
                            </div>
                            <div id="messagePreview" class="text-gray-900 text-sm whitespace-pre-wrap">
                                {{ __('اكتب رسالتك لرؤية المعاينة...') }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2">
                                {{ now()->format('H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المتغيرات المتاحة -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-code ml-2"></i>
                    {{ __('المتغيرات المتاحة') }}
                </h3>
                
                <div class="text-sm text-gray-600 space-y-2">
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{student_name}</code> - اسم الطالب</div>
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{parent_name}</code> - اسم ولي الأمر (إن وجد)</div>
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{courses_count}</code> - عدد الكورسات</div>
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{avg_score}</code> - متوسط الدرجات</div>
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{month_name}</code> - اسم الشهر</div>
                    <div><code class="bg-gray-100 px-2 py-1 rounded">{date}</code> - التاريخ</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.querySelector('textarea[name="message"]');
    const messagePreview = document.getElementById('messagePreview');
    const templateSelect = document.querySelector('select[name="template_id"]');
    const form = document.getElementById('messageForm');

    // تعبئة مسبقة من القالب (إن وُجد)
    @if(!empty($prefillMessage))
        messageTextarea.value = @json($prefillMessage);
        messagePreview.textContent = messageTextarea.value;
    @endif

    // تحديث المعاينة
    function updatePreview() {
        const message = messageTextarea.value || '{{ __("اكتب رسالتك لرؤية المعاينة...") }}';
        messagePreview.textContent = message;
    }

    // استمع لتغييرات النص
    messageTextarea.addEventListener('input', updatePreview);

    // استمع لتغيير القالب
    templateSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const templateContent = selectedOption.getAttribute('data-content');
            if (templateContent) {
                messageTextarea.value = templateContent;
                updatePreview();
            }
        }
    });

    // تحديد action للفورم حسب نوع المستلمين
    function updateFormAction() {
        let checked = document.querySelector('input[name="recipient_type"]:checked');
        if (!checked) {
            checked = document.querySelector('input[name="recipient_type"][value="single"]');
            if (checked) {
                checked.checked = true;
            }
        }
        const recipientType = checked ? checked.value : 'single';
        if (recipientType === 'single') {
            form.action = '{{ route("admin.messages.send-single") }}';
        } else {
            form.action = '{{ route("admin.messages.send-bulk") }}';
        }
    }

    // استمع لتغيير نوع المستلمين
    document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
        radio.addEventListener('change', updateFormAction);
    });

    // تحديد action الأولي
    updateFormAction();
});

function previewMessage() {
    const message = document.querySelector('textarea[name="message"]').value;
    if (!message.trim()) {
        alert('{{ __("يرجى كتابة نص الرسالة أولاً") }}');
        return;
    }
    
    // يمكن إضافة مودال معاينة أكثر تفصيلاً هنا
    alert('{{ __("المعاينة متاحة في الشريط الجانبي") }}');
}
</script>
@endpush
@endsection
