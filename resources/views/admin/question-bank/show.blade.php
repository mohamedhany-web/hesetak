@extends('layouts.admin')

@section('title', 'تفاصيل السؤال - ' . config('app.name'))
@section('page_title', 'تفاصيل السؤال')

@section('content')
@php
    $cardClass = 'rounded-2xl border border-line bg-surface shadow-soft';
    $typeClass = match($question->type) {
        'multiple_choice' => 'bg-accent-soft text-accent',
        'true_false' => 'bg-emerald-50 text-emerald-700',
        'fill_blank' => 'bg-amber-50 text-amber-800',
        'essay' => 'bg-[#f2f5f4] text-muted',
        default => 'bg-[#f2f5f4] text-muted',
    };
    $difficultyClass = match($question->difficulty_level) {
        'easy' => 'bg-emerald-50 text-emerald-700',
        'medium' => 'bg-amber-50 text-amber-800',
        default => 'bg-rose-50 text-rose-700',
    };
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحتوى · بنك الأسئلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تفاصيل السؤال</h2>
            <p class="mt-1 max-w-2xl truncate text-sm text-muted">{{ Str::limit(strip_tags($question->question), 80) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.question-bank.edit', $question) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-edit text-xs"></i> تعديل
            </a>
            <form action="{{ route('admin.question-bank.duplicate', $question) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    <i class="fas fa-copy text-xs"></i> نسخ
                </button>
            </form>
            <form action="{{ route('admin.question-bank.destroy', $question) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السؤال؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-rose-700 hover:bg-rose-50">
                    <i class="fas fa-trash text-xs"></i> حذف
                </button>
            </form>
            <a href="{{ route('admin.question-bank.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i> العودة
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-4">
        <div class="space-y-5 xl:col-span-3">
            <article class="{{ $cardClass }}">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                    <h3 class="text-sm font-semibold text-ink">نص السؤال</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $typeClass }}">{{ $question->type_text }}</span>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $difficultyClass }}">{{ $question->difficulty_text }}</span>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $question->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ $question->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="mb-4 text-base leading-relaxed text-ink">{{ $question->question }}</div>

                    @if($question->hasMedia())
                        <div class="mt-4 space-y-4">
                            @if($question->image_url)
                                <div>
                                    <h4 class="mb-2 text-xs font-medium text-muted">الصورة المرفقة:</h4>
                                    <div class="group relative">
                                        <img src="{{ $question->getImageUrl() }}" alt="صورة السؤال" loading="lazy"
                                             class="max-w-full cursor-pointer rounded-xl border border-line transition hover:scale-[1.02]"
                                             onclick="openImageModal(this.src)">
                                        <div class="absolute right-2 top-2 opacity-0 transition group-hover:opacity-100">
                                            <button type="button" onclick="openImageModal('{{ $question->getImageUrl() }}')"
                                                    class="rounded-full bg-black/50 p-2 text-white hover:bg-black/70">
                                                <i class="fas fa-search-plus text-xs"></i>
                                            </button>
                                        </div>
                                        <p class="mt-2 text-xs text-muted"><i class="fas fa-info-circle ml-1"></i> انقر على الصورة للتكبير</p>
                                    </div>
                                </div>
                            @endif

                            @if($question->audio_url)
                                <div>
                                    <h4 class="mb-2 text-xs font-medium text-muted">الملف الصوتي:</h4>
                                    <audio controls class="w-full max-w-sm">
                                        <source src="{{ $question->audio_url }}" type="audio/mpeg">
                                        المتصفح لا يدعم تشغيل الملفات الصوتية
                                    </audio>
                                </div>
                            @endif

                            @if($question->video_url)
                                <div>
                                    <h4 class="mb-2 text-xs font-medium text-muted">الفيديو:</h4>
                                    @if(str_contains($question->video_url, 'youtube.com') || str_contains($question->video_url, 'youtu.be'))
                                        @php
                                            $videoId = null;
                                            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $question->video_url, $matches)) {
                                                $videoId = $matches[1];
                                            }
                                        @endphp
                                        @if($videoId)
                                            <div class="overflow-hidden rounded-xl border border-line">
                                                <iframe width="560" height="315"
                                                        src="https://www.youtube.com/embed/{{ $videoId }}"
                                                        title="فيديو السؤال"
                                                        frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen
                                                        loading="lazy"
                                                        class="aspect-video w-full max-w-lg">
                                                </iframe>
                                            </div>
                                        @endif
                                    @else
                                        <video controls preload="metadata" class="w-full max-w-lg rounded-xl border border-line">
                                            <source src="{{ $question->video_url }}" type="video/mp4">
                                            <p class="text-rose-600">المتصفح لا يدعم تشغيل الفيديو</p>
                                        </video>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </article>

            @if(in_array($question->type, ['multiple_choice', 'true_false']))
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">الخيارات والإجابات</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        @if($question->options && is_array($question->options))
                            @php
                                $normalizedCorrectAnswers = $question->normalizeMultipleChoiceCorrectAnswers();
                            @endphp
                            <div class="space-y-2">
                                @foreach($question->options as $index => $option)
                                    <div class="flex items-center rounded-xl border p-3 {{ in_array((int)$index, $normalizedCorrectAnswers, true) ? 'border-emerald-200 bg-emerald-50' : 'border-line bg-[#f8faf9]' }}">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-medium {{ in_array((int)$index, $normalizedCorrectAnswers, true) ? 'bg-emerald-100 text-emerald-800' : 'bg-[#f2f5f4] text-muted' }}">
                                            {{ chr(65 + $index) }}
                                        </span>
                                        <span class="mr-3 flex-1 text-sm text-ink">{{ $option }}</span>
                                        @if(in_array((int)$index, $normalizedCorrectAnswers, true))
                                            <i class="fas fa-check text-emerald-600"></i>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @elseif($question->type == 'fill_blank')
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">الإجابة الصحيحة</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        @if($question->correct_answer)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                @if(is_array($question->correct_answer))
                                    @foreach($question->correct_answer as $answer)
                                        <span class="mb-2 ml-2 inline-block rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-800">{{ $answer }}</span>
                                    @endforeach
                                @else
                                    <span class="font-medium text-emerald-800">{{ $question->correct_answer }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </article>
            @endif

            @if($question->explanation)
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">شرح الإجابة</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="rounded-xl border border-line bg-[#f8faf9] p-4 text-sm text-ink">
                            {{ $question->explanation }}
                        </div>
                    </div>
                </article>
            @endif
        </div>

        <div class="space-y-5">
            <article class="{{ $cardClass }}">
                <div class="border-b border-line px-4 py-3">
                    <h3 class="text-sm font-semibold text-ink">معلومات السؤال</h3>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div>
                        <div class="text-xs text-muted">النقاط</div>
                        <div class="text-lg font-semibold tabular-nums text-ink">{{ $question->points }}</div>
                    </div>
                    @if($question->time_limit)
                        <div>
                            <div class="text-xs text-muted">الوقت المحدد</div>
                            <div class="text-lg font-semibold tabular-nums text-ink">{{ $question->time_limit }} ثانية</div>
                        </div>
                    @endif
                    @if($question->category)
                        <div>
                            <div class="text-xs text-muted">التصنيف</div>
                            <div class="text-sm font-medium text-ink">{{ $question->category->name }}</div>
                            @if($question->category->academicYear)
                                <div class="text-xs text-muted">{{ $question->category->academicYear->name }}</div>
                            @endif
                            @if($question->category->academicSubject)
                                <div class="text-xs text-muted">{{ $question->category->academicSubject->name }}</div>
                            @endif
                        </div>
                    @endif
                    <div>
                        <div class="text-xs text-muted">تاريخ الإنشاء</div>
                        <div class="text-sm font-medium tabular-nums text-ink">{{ $question->created_at->format('Y-m-d') }}</div>
                        <div class="text-xs text-muted">{{ $question->created_at->diffForHumans() }}</div>
                    </div>
                    @if($question->updated_at != $question->created_at)
                        <div>
                            <div class="text-xs text-muted">آخر تحديث</div>
                            <div class="text-sm font-medium tabular-nums text-ink">{{ $question->updated_at->format('Y-m-d') }}</div>
                            <div class="text-xs text-muted">{{ $question->updated_at->diffForHumans() }}</div>
                        </div>
                    @endif
                </div>
            </article>

            @if($question->tags)
                <article class="{{ $cardClass }}">
                    <div class="border-b border-line px-4 py-3">
                        <h3 class="text-sm font-semibold text-ink">العلامات</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap gap-2">
                            @foreach($question->tags as $tag)
                                <span class="inline-flex items-center rounded-full bg-[#f2f5f4] px-3 py-1 text-xs font-medium text-ink">
                                    <i class="fas fa-tag ml-1 text-[10px] text-muted"></i>
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endif

            <article class="{{ $cardClass }}">
                <div class="border-b border-line px-4 py-3">
                    <h3 class="text-sm font-semibold text-ink">إحصائيات</h3>
                </div>
                <div class="p-4 text-center sm:p-5">
                    <div class="text-2xl font-semibold tabular-nums text-accent">{{ $question->examQuestions()->count() }}</div>
                    <div class="mt-1 text-xs text-muted">مرات الاستخدام في الامتحانات</div>
                </div>
            </article>
        </div>
    </div>
</div>

<div id="imageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/75 p-4">
    <div class="relative max-h-full max-w-4xl">
        <button type="button" onclick="closeImageModal()"
                class="absolute right-4 top-4 z-10 flex size-10 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="صورة مكبرة" class="max-h-full max-w-full rounded-xl shadow-2xl">
    </div>
</div>

@push('scripts')
<script>
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    modalImage.src = imageSrc;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.style.overflow = 'auto';
}

document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endpush
@endsection
