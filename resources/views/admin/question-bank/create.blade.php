@extends('layouts.admin')

@section('title', 'إضافة سؤال جديد - ' . config('app.name'))
@section('page_title', 'إضافة سؤال جديد')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $textareaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $cardClass = 'rounded-2xl border border-line bg-surface shadow-soft';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحتوى · بنك الأسئلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة سؤال جديد</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">أنشئ سؤالاً جديداً لبنك الأسئلة مع الخيارات والوسائط.</p>
        </div>
        <a href="{{ route('admin.question-bank.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            العودة
        </a>
    </section>

    <form action="{{ route('admin.question-bank.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">معلومات السؤال</h3>
                    </div>
                    <div class="space-y-5 p-4 sm:p-5">
                        <div>
                            <label for="question" class="{{ $labelClass }}">نص السؤال <span class="text-rose-500">*</span></label>
                            <textarea name="question" id="question" rows="4" required class="{{ $textareaClass }}" placeholder="اكتب نص السؤال هنا...">{{ old('question') }}</textarea>
                            @error('question')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="type" class="{{ $labelClass }}">نوع السؤال <span class="text-rose-500">*</span></label>
                                <select name="type" id="type" required onchange="toggleQuestionFields()" class="{{ $fieldClass }}">
                                    <option value="">اختر نوع السؤال</option>
                                    @foreach(\App\Models\Question::getQuestionTypes() as $key => $type)
                                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="difficulty_level" class="{{ $labelClass }}">مستوى الصعوبة <span class="text-rose-500">*</span></label>
                                <select name="difficulty_level" id="difficulty_level" required class="{{ $fieldClass }}">
                                    <option value="">اختر مستوى الصعوبة</option>
                                    @foreach(\App\Models\Question::getDifficultyLevels() as $key => $level)
                                        <option value="{{ $key }}" {{ old('difficulty_level') == $key ? 'selected' : '' }}>{{ $level }}</option>
                                    @endforeach
                                </select>
                                @error('difficulty_level')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="points" class="{{ $labelClass }}">درجة السؤال <span class="text-rose-500">*</span></label>
                                <input type="number" name="points" id="points" step="0.5" min="0.5" max="100" value="{{ old('points', 1) }}" required class="{{ $fieldClass }}" placeholder="1.0">
                                @error('points')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="time_limit" class="{{ $labelClass }}">الوقت المحدد (ثانية)</label>
                                <input type="number" name="time_limit" id="time_limit" min="10" max="600" value="{{ old('time_limit') }}" class="{{ $fieldClass }}" placeholder="اتركه فارغاً لاستخدام وقت الامتحان العام">
                                @error('time_limit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </article>

                <article id="question-options" class="{{ $cardClass }}" style="display: none;">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">خيارات السؤال</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="multiple-choice-options" style="display: none;">
                            <div class="space-y-3">
                                @for($i = 1; $i <= 5; $i++)
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="correct_option" value="{{ $i - 1 }}" id="correct_{{ $i }}" class="size-4 border-line text-accent focus:ring-accent/20">
                                        <label for="option_{{ $i }}" class="shrink-0 text-xs font-medium text-muted">الخيار {{ $i }}:</label>
                                        <input type="text" name="option_{{ $i }}" id="option_{{ $i }}" value="{{ old('option_' . $i) }}"
                                               class="h-11 flex-1 rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                                               placeholder="اكتب الخيار {{ $i }} {{ $i <= 2 ? '(مطلوب)' : '(اختياري)' }}"
                                               {{ $i <= 2 ? 'required' : '' }}>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <div id="true-false-options" style="display: none;">
                            <label class="{{ $labelClass }}">الإجابة الصحيحة:</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="true_false_answer" value="صح" {{ old('true_false_answer') == 'صح' ? 'checked' : '' }} class="size-4 border-line text-accent focus:ring-accent/20">
                                    <span class="text-sm font-medium text-ink">صح</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="true_false_answer" value="خطأ" {{ old('true_false_answer') == 'خطأ' ? 'checked' : '' }} class="size-4 border-line text-accent focus:ring-accent/20">
                                    <span class="text-sm font-medium text-ink">خطأ</span>
                                </label>
                            </div>
                        </div>

                        <div id="fill-blank-options" style="display: none;">
                            <label for="correct_answers" class="{{ $labelClass }}">الإجابات الصحيحة (مفصولة بفواصل)</label>
                            <input type="text" name="correct_answers" id="correct_answers" value="{{ old('correct_answers') }}" class="{{ $fieldClass }}" placeholder="الإجابة الأولى, الإجابة الثانية, ...">
                            <p class="mt-1 text-xs text-muted">يمكنك إدخال عدة إجابات صحيحة مفصولة بفواصل</p>
                        </div>

                        <div id="text-answer-options" style="display: none;">
                            <label for="model_answer" class="{{ $labelClass }}">الإجابة النموذجية (اختياري)</label>
                            <textarea name="model_answer" id="model_answer" rows="4" class="{{ $textareaClass }}" placeholder="اكتب الإجابة النموذجية للمساعدة في التصحيح...">{{ old('model_answer') }}</textarea>
                            <p class="mt-1 text-xs text-muted">ستساعد في التصحيح اليدوي</p>
                        </div>

                        <div id="matching-options" style="display: none;">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="left_items" class="{{ $labelClass }}">العناصر اليسرى (كل عنصر في سطر)</label>
                                    <textarea name="left_items" id="left_items" rows="5" class="{{ $textareaClass }}" placeholder="العنصر الأول&#10;العنصر الثاني&#10;العنصر الثالث">{{ old('left_items') }}</textarea>
                                </div>
                                <div>
                                    <label for="right_items" class="{{ $labelClass }}">العناصر اليمنى (كل عنصر في سطر)</label>
                                    <textarea name="right_items" id="right_items" rows="5" class="{{ $textareaClass }}" placeholder="المطابق الأول&#10;المطابق الثاني&#10;المطابق الثالث">{{ old('right_items') }}</textarea>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="{{ $labelClass }}">المطابقات الصحيحة</label>
                                <div id="matching-pairs" class="space-y-2"></div>
                            </div>
                        </div>

                        <div id="ordering-options" style="display: none;">
                            <label for="ordering_items" class="{{ $labelClass }}">العناصر للترتيب (كل عنصر في سطر)</label>
                            <textarea name="ordering_items" id="ordering_items" rows="5" class="{{ $textareaClass }}" placeholder="العنصر الأول&#10;العنصر الثاني&#10;العنصر الثالث&#10;العنصر الرابع">{{ old('ordering_items') }}</textarea>
                            <p class="mt-1 text-xs text-muted">اكتب العناصر بالترتيب الصحيح</p>
                        </div>
                    </div>
                </article>

                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">الوسائط المرفقة</h3>
                    </div>
                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <label for="image" class="{{ $labelClass }}">رفع صورة</label>
                            <input type="file" name="image" id="image" accept="image/*" class="{{ $fieldClass }} file:ml-2 file:rounded-lg file:border-0 file:bg-[#f2f5f4] file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-accent">
                            <p class="mt-1 text-xs text-muted">الحد الأقصى: 40 ميجابايت. الأنواع المدعومة: JPG, PNG, GIF</p>
                        </div>
                        <div>
                            <label for="image_url" class="{{ $labelClass }}">أو رابط صورة خارجي</label>
                            <input type="url" name="image_url" id="image_url" value="{{ old('image_url') }}" class="{{ $fieldClass }}" placeholder="https://example.com/image.jpg" dir="ltr">
                        </div>
                        <div>
                            <label for="audio_url" class="{{ $labelClass }}">رابط ملف صوتي</label>
                            <input type="url" name="audio_url" id="audio_url" value="{{ old('audio_url') }}" class="{{ $fieldClass }}" placeholder="https://example.com/audio.mp3" dir="ltr">
                        </div>
                        <div>
                            <label for="video_url" class="{{ $labelClass }}">رابط فيديو</label>
                            <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}" class="{{ $fieldClass }}" placeholder="https://www.youtube.com/watch?v=... أو أي رابط فيديو" dir="ltr">
                        </div>
                    </div>
                </article>

                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">شرح الإجابة</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <textarea name="explanation" id="explanation" rows="4" class="{{ $textareaClass }}" placeholder="اكتب شرحاً مفصلاً للإجابة الصحيحة (اختياري)...">{{ old('explanation') }}</textarea>
                        <p class="mt-1 text-xs text-muted">سيظهر للطلاب بعد الانتهاء من الامتحان (حسب إعدادات الامتحان)</p>
                    </div>
                </article>
            </div>

            <div class="space-y-5">
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">التصنيف والتاجز</h3>
                    </div>
                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <label for="category_id" class="{{ $labelClass }}">التصنيف <span class="text-rose-500">*</span></label>
                            <select name="category_id" id="category_id" required class="{{ $fieldClass }}">
                                <option value="">اختر التصنيف</option>
                                @forelse($categories as $category)
                                    <option value="{{ $category->id }}" {{ (old('category_id', $selectedCategory) == $category->id) ? 'selected' : '' }}>{{ $category->full_path }}{{ empty($category->is_active) ? ' (غير نشط)' : '' }}</option>
                                @empty
                                    <option value="" disabled>لا توجد تصنيفات — أنشئ تصنيفاً أولاً</option>
                                @endforelse
                            </select>
                            @if($categories->isEmpty() && Route::has('admin.question-categories.create'))
                                <p class="mt-1 text-xs text-amber-700">
                                    <a href="{{ route('admin.question-categories.create') }}" class="underline">إضافة تصنيف جديد</a>
                                    ثم ارجع لهذه الصفحة.
                                </p>
                            @endif
                            @error('category_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="tags" class="{{ $labelClass }}">التاجز (مفصولة بفواصل)</label>
                            <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="{{ $fieldClass }}" placeholder="رياضيات, جبر, معادلات">
                            <p class="mt-1 text-xs text-muted">ستساعد في البحث والتصنيف</p>
                        </div>
                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="size-4 rounded border-line text-accent focus:ring-accent/20">
                                <span class="text-sm font-medium text-ink">سؤال نشط</span>
                            </label>
                        </div>
                    </div>
                </article>

                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">معاينة السؤال</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="question-preview" class="min-h-32 rounded-xl bg-[#f8faf9] p-4 text-center text-sm text-muted">
                            اكتب السؤال لرؤية المعاينة
                        </div>
                    </div>
                </article>

                <article class="{{ $cardClass }}">
                    <div class="space-y-3 p-4 sm:p-5">
                        <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                            <i class="fas fa-save text-xs"></i> حفظ السؤال
                        </button>
                        <button type="submit" name="save_and_new" value="1" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                            <i class="fas fa-plus text-xs"></i> حفظ وإضافة آخر
                        </button>
                        <a href="{{ route('admin.question-bank.index') }}" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-line px-4 text-sm font-medium text-muted hover:bg-[#f8faf9]">
                            إلغاء
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleQuestionFields() {
    const type = document.getElementById('type').value;

    document.getElementById('question-options').style.display = 'none';
    document.getElementById('multiple-choice-options').style.display = 'none';
    document.getElementById('true-false-options').style.display = 'none';
    document.getElementById('fill-blank-options').style.display = 'none';
    document.getElementById('text-answer-options').style.display = 'none';
    document.getElementById('matching-options').style.display = 'none';
    document.getElementById('ordering-options').style.display = 'none';

    if (type) {
        document.getElementById('question-options').style.display = 'block';

        switch(type) {
            case 'multiple_choice':
                document.getElementById('multiple-choice-options').style.display = 'block';
                break;
            case 'true_false':
                document.getElementById('true-false-options').style.display = 'block';
                break;
            case 'fill_blank':
                document.getElementById('fill-blank-options').style.display = 'block';
                break;
            case 'short_answer':
            case 'essay':
                document.getElementById('text-answer-options').style.display = 'block';
                break;
            case 'matching':
                document.getElementById('matching-options').style.display = 'block';
                break;
            case 'ordering':
                document.getElementById('ordering-options').style.display = 'block';
                break;
        }
    }

    updatePreview();
}

function updatePreview() {
    const question = document.getElementById('question').value;
    const type = document.getElementById('type').value;
    const preview = document.getElementById('question-preview');

    if (!question) {
        preview.innerHTML = '<div class="text-center text-muted">اكتب السؤال لرؤية المعاينة</div>';
        return;
    }

    let previewHtml = `<div class="text-right"><strong>السؤال:</strong> ${question}</div>`;

    if (type === 'multiple_choice') {
        previewHtml += '<div class="mt-3"><strong>الخيارات:</strong>';
        for (let i = 1; i <= 5; i++) {
            const option = document.getElementById(`option_${i}`).value;
            if (option) {
                previewHtml += `<div class="mt-1">○ ${option}</div>`;
            }
        }
        previewHtml += '</div>';
    } else if (type === 'true_false') {
        previewHtml += '<div class="mt-3"><strong>الخيارات:</strong><div class="mt-1">○ صح</div><div class="mt-1">○ خطأ</div></div>';
    }

    preview.innerHTML = previewHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    toggleQuestionFields();

    document.getElementById('question').addEventListener('input', updatePreview);
    document.getElementById('type').addEventListener('change', updatePreview);

    for (let i = 1; i <= 5; i++) {
        const option = document.getElementById(`option_${i}`);
        if (option) {
            option.addEventListener('input', updatePreview);
        }
    }
});
</script>
@endpush
@endsection
