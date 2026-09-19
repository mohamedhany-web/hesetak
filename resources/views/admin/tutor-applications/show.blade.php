@extends('layouts.admin')

@section('title', 'بيانات طلب معلم - حصتك')
@section('page_title', 'مراجعة طلب التوظيف')

@section('content')
@php
    $videoUrl = $application->introVideoDisplayUrl();
    $embed = $videoUrl ? \App\Helpers\VideoHelper::getEmbedUrl($videoUrl) : null;
    $direct = $videoUrl ? \App\Helpers\VideoHelper::getDirectVideoUrl($videoUrl) : null;
    if (! $direct && $application->introVideoFileUrl()) {
        $direct = $application->introVideoFileUrl();
    }
    $photoInline = $photoInline ?? null;
    $idInline = $idInline ?? null;
    $certificateInline = $certificateInline ?? null;
    $photoFileUrl = $application->photo_path
        ? route('admin.tutor-applications.file', [$application, 'photo'])
        : null;
    $idFileUrl = $application->id_document_path
        ? route('admin.tutor-applications.file', [$application, 'id'])
        : null;
    $certificateFileUrl = $application->certificate_path
        ? route('admin.tutor-applications.file', [$application, 'certificate'])
        : null;
    $genderLabel = match ($application->gender) {
        'male' => 'ذكر',
        'female' => 'أنثى',
        default => '—',
    };
    $badgeClass = match ($application->status) {
        'activated' => 'bg-accent-soft text-accent',
        'approved' => 'bg-metal/15 text-metal',
        'rejected' => 'bg-danger/10 text-danger',
        default => 'bg-canvas-muted text-muted',
    };
    $fieldClass = 'w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التوظيف · طلب #{{ $application->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $application->full_name }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $application->headline ?: 'مراجعة طلب التوظيف' }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap items-center gap-2">
            <span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">{{ $application->statusLabel() }}</span>
            <a href="{{ route('admin.tutor-applications.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">كل الطلبات</a>
            <a href="{{ route('admin.tutor-applications.hub') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">لوحة التوظيف</a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-danger/20 bg-surface px-4 py-3 text-sm font-medium text-danger shadow-soft" role="alert">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-danger/10"><i class="fas fa-exclamation text-sm"></i></span>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @if(session('activated_email'))
        <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-user-check text-sm"></i></span>
                <div>
                    <p class="text-sm font-semibold text-ink">تم تفعيل الحساب — المعلم يسجّل بنفس الإيميل وكلمة المرور وتُفتح له لوحة المعلم:</p>
                    <p class="mt-2 font-mono text-sm font-bold text-accent" dir="ltr">{{ session('activated_email') }}</p>
                    @if(session('activated_user_id'))
                        <a href="{{ route('admin.users.edit', session('activated_user_id')) }}" class="mt-2 inline-flex text-sm font-semibold text-accent underline">فتح صفحة المستخدم</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
        <div class="space-y-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="flex items-start gap-4">
                    @if($photoInline)
                        <img src="{{ $photoInline }}" alt="" class="size-20 rounded-2xl border border-line object-cover">
                    @else
                        <span class="inline-flex size-20 items-center justify-center rounded-2xl bg-canvas-muted text-xl font-bold text-muted">{{ mb_substr($application->full_name, 0, 1) }}</span>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold text-ink">{{ $application->full_name }}</h3>
                        <p class="mt-0.5 font-medium text-accent">{{ $application->headline }}</p>
                        <p class="mt-1 text-xs text-muted">رقم الطلب #{{ $application->id }} · {{ $application->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                </div>

                <h4 class="mt-5 border-t border-line pt-4 text-sm font-semibold text-ink">البيانات الشخصية</h4>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    @foreach([
                        ['البريد', $application->email, true],
                        ['الجوال / واتساب', $application->phone ?: '—', true],
                        ['النوع', $genderLabel, false],
                        ['الجنسية', $application->nationality ?: '—', false],
                        ['الدولة / المدينة', $application->city ?: '—', false],
                        ['المؤهل', $application->education ?: '—', false],
                        ['سنوات الخبرة', $application->years_experience ?? '—', false],
                        ['راجع بواسطة', $application->reviewedByUser->name ?? '—', false],
                    ] as [$label, $value, $ltr])
                        <div class="rounded-xl border border-line bg-canvas px-3 py-2">
                            <dt class="text-xs text-muted">{{ $label }}</dt>
                            <dd class="mt-0.5 font-semibold text-ink" @if($ltr) dir="ltr" @endif>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </article>

            @if(is_array($application->answers) && count($application->answers))
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <h3 class="text-base font-semibold text-ink">إجابات نموذج التقديم</h3>
                    <dl class="mt-3 grid gap-3 text-sm">
                        @foreach($application->answers as $answer)
                            @php
                                $label = $answer['label'] ?? 'حقل';
                                $isFile = ($answer['type'] ?? '') === 'file';
                                $val = $isFile
                                    ? ($answer['name'] ?? basename($answer['path'] ?? ''))
                                    : (is_array($answer['value'] ?? null) ? implode('، ', $answer['value']) : ($answer['value'] ?? '—'));
                                $fileUrl = $isFile && !empty($answer['path'])
                                    ? \App\Services\TutorApplicationStorage::publicUrl($answer['path'])
                                    : null;
                            @endphp
                            <div class="rounded-xl border border-line bg-canvas px-3 py-2">
                                <dt class="text-xs text-muted">{{ $label }}</dt>
                                <dd class="mt-0.5 font-semibold text-ink">
                                    @if($fileUrl)
                                        <a href="{{ $fileUrl }}" target="_blank" class="text-accent underline">{{ $val ?: 'فتح الملف' }}</a>
                                    @else
                                        <span class="whitespace-pre-line">{{ $val !== '' && $val !== null ? $val : '—' }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endif

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-base font-semibold text-ink">النبذة / السيرة</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-muted">{{ $application->bio }}</p>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-base font-semibold text-ink">الخبرات</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-muted">{{ $application->experience }}</p>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-base font-semibold text-ink">الفيديو التعريفي</h3>
                <div class="mt-3">
                    @if($embed)
                        <div class="aspect-video overflow-hidden rounded-xl bg-ink">
                            <iframe src="{{ $embed }}" class="h-full w-full" allowfullscreen></iframe>
                        </div>
                    @elseif($direct)
                        <video controls class="w-full rounded-xl bg-ink" src="{{ $direct }}"></video>
                    @elseif($videoUrl)
                        <a href="{{ $videoUrl }}" target="_blank" class="font-semibold text-accent underline" dir="ltr">{{ $videoUrl }}</a>
                    @else
                        <p class="text-sm text-muted">لا يوجد فيديو.</p>
                    @endif
                </div>
            </article>

            @if($application->user)
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-user-check text-sm"></i></span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-ink">الحساب المفعّل</h3>
                            <p class="mt-1 text-sm text-muted">المستخدم: <span class="font-semibold text-ink">{{ $application->user->name }}</span></p>
                            <p class="mt-0.5 text-sm text-muted" dir="ltr">{{ $application->user->email }} · {{ $application->user->phone }}</p>
                            <p class="mt-1 text-xs text-muted">تفعيل: {{ $application->activated_at?->format('Y-m-d H:i') }} بواسطة {{ $application->activatedByUser->name ?? '—' }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('admin.users.edit', $application->user) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-3 text-xs font-semibold text-ink hover:text-accent">إدارة المستخدم</a>
                                <a href="{{ route('public.instructors.show', $application->user) }}" target="_blank" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-3 text-xs font-semibold text-white">الملف العام</a>
                            </div>
                        </div>
                    </div>
                </article>
            @endif
        </div>

        <div class="space-y-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-4">
                <h3 class="text-base font-semibold text-ink">المستندات المرفوعة</h3>
                @foreach([
                    ['label' => 'الصورة الشخصية', 'url' => $photoFileUrl, 'inline' => $photoInline, 'pdf' => false],
                    ['label' => 'البطاقة / الجواز', 'url' => $idFileUrl, 'inline' => $idInline, 'pdf' => $application->idDocumentIsPdf()],
                    ['label' => 'الشهادة / الإجازة', 'url' => $certificateFileUrl, 'inline' => $certificateInline, 'pdf' => $application->certificateIsPdf()],
                ] as $doc)
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-muted">{{ $doc['label'] }}</p>
                        @if($doc['inline'] && ! $doc['pdf'])
                            <div class="overflow-hidden rounded-xl border border-line bg-canvas">
                                <img src="{{ $doc['inline'] }}" alt="{{ $doc['label'] }}" class="max-h-48 w-full object-contain">
                            </div>
                        @elseif($doc['url'])
                            @if($doc['pdf'])
                                <a href="{{ $doc['url'] }}" target="_blank" rel="noopener" class="btn-press inline-flex items-center gap-2 rounded-xl border border-line bg-canvas px-4 py-3 text-sm font-semibold text-accent">
                                    <i class="fas fa-file-pdf text-danger"></i> فتح ملف PDF
                                </a>
                            @else
                                <p class="text-sm text-danger">الملف مسجّل لكن تعذر قراءته من التخزين. اطلب إعادة الرفع.</p>
                            @endif
                        @else
                            <p class="text-sm text-muted">غير مرفوع</p>
                        @endif
                    </div>
                @endforeach
            </article>

            @if($application->status === 'pending')
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-3">
                    <h3 class="text-base font-semibold text-ink">١) قبول الطلب بعد المراجعة</h3>
                    <p class="text-xs text-muted">بعد القبول يمكنك تفعيل حساب المعلم في الخطوة التالية.</p>
                    <form method="POST" action="{{ route('admin.tutor-applications.approve', $application) }}">
                        @csrf
                        <button class="btn-press w-full rounded-xl bg-accent py-2.5 text-sm font-semibold text-white">قبول الطلب</button>
                    </form>
                    <form method="POST" action="{{ route('admin.tutor-applications.reject', $application) }}" class="space-y-2">
                        @csrf
                        <textarea name="admin_notes" rows="3" class="{{ $fieldClass }}" placeholder="سبب الرفض (اختياري)">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                        <button class="btn-press w-full rounded-xl bg-danger py-2.5 text-sm font-semibold text-white">رفض الطلب</button>
                    </form>
                </article>
            @endif

            @if($application->canActivateAccount())
                <article class="rounded-2xl border border-accent/30 bg-surface p-5 shadow-soft space-y-3">
                    <h3 class="text-base font-semibold text-ink">٢) تفعيل الحساب ولوحة المعلم</h3>
                    <p class="text-sm leading-6 text-muted">
                        الحساب موجود مسبقاً. التفعيل يفتح لوحة المعلم ولوحات التحكم، ويعتمد الملف التعريفي للظهور للطلاب.
                    </p>
                    @if($application->user)
                        <p class="text-xs font-medium text-accent" dir="ltr">{{ $application->user->email }}</p>
                    @endif
                    <form method="POST" action="{{ route('admin.tutor-applications.activate', $application) }}" onsubmit="return confirm('تأكيد تفعيل الحساب ولوحة المعلم؟')">
                        @csrf
                        <button class="btn-press w-full rounded-xl bg-accent py-3 text-sm font-semibold text-white">
                            <i class="fas fa-user-check ml-1"></i> تفعيل الحساب الآن
                        </button>
                    </form>
                </article>
            @endif

            @if($application->admin_notes && $application->status === 'rejected')
                <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                    <h3 class="text-base font-semibold text-ink">ملاحظات الرفض</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $application->admin_notes }}</p>
                </article>
            @endif

            @unless($application->isActivated())
                <form method="POST" action="{{ route('admin.tutor-applications.destroy', $application) }}" onsubmit="return confirm('حذف هذا الطلب نهائياً؟')">
                    @csrf
                    @method('DELETE')
                    <button class="btn-press w-full rounded-xl border border-danger/30 py-2.5 text-sm font-semibold text-danger hover:bg-danger hover:text-white">حذف الطلب</button>
                </form>
            @endunless
        </div>
    </div>
</div>
@endsection
