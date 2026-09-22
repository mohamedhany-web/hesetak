@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_library_materials'))

@section('content')
@php
    $locale = app()->getLocale();
    $materials = $materials ?? collect();
    $searchQuery = $searchQuery ?? '';
    $courseId = (int) ($courseId ?? 0);
    $lectureId = (int) ($lectureId ?? 0);
    $typeFilter = $typeFilter ?? 'all';
    $sort = $sort ?? 'newest';
    $courses = $courses ?? collect();
    $lectures = $lectures ?? collect();
    $typeCounts = $typeCounts ?? [];
    $libraryFolders = $libraryFolders ?? collect();
    $activeFolder = $activeFolder ?? null;
    $uncategorizedCount = (int) ($uncategorizedCount ?? 0);
    $themeFilter = $themeFilter ?? '';
    $familyThemes = $familyThemes ?? \App\Support\FamilyLibraryThemes::all();
    $tones = ['blue', 'pink', 'orange', 'purple', 'green'];
    $hasLibraryEntitlement = (bool) ($hasLibraryEntitlement ?? false);
    $linkedTeacherCount = (int) ($linkedTeacherCount ?? 0);
    $academyFolderCount = (int) ($academyFolderCount ?? 0);
    $teacherFolderCount = (int) ($teacherFolderCount ?? 0);
    $packagesUrl = $packagesUrl ?? route('dashboard');
    $academyFolders = $academyFolders ?? collect();
    $teacherFolders = $teacherFolders ?? collect();

    $materialCount = method_exists($materials, 'total') ? $materials->total() : collect($materials)->count();
    $materialsEmpty = method_exists($materials, 'isEmpty') ? $materials->isEmpty() : collect($materials)->isEmpty();
    $pageItems = $materials instanceof \Illuminate\Contracts\Pagination\Paginator
        ? collect($materials->items())
        : collect($materials);
    $academyMaterials = $pageItems->filter(fn ($m) => ! ($m->folder?->instructor_id))->values();
    $teacherMaterials = $pageItems->filter(fn ($m) => (bool) ($m->folder?->instructor_id))->values();
    $showSections = ! $activeFolder && ! $materialsEmpty && ($academyMaterials->isNotEmpty() || $teacherMaterials->isNotEmpty());
    $hasFilters = $courseId || $lectureId || $typeFilter !== 'all' || $searchQuery !== '' || $activeFolder || $themeFilter;

    $fileMeta = function ($material) {
        $name = (string) ($material->file_name ?: $material->file_path ?: '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $map = [
            'pdf' => ['fa' => 'fas fa-file-pdf', 'label' => 'PDF', 'tone' => 'pink'],
            'doc' => ['fa' => 'fas fa-file-word', 'label' => 'DOC', 'tone' => 'blue'],
            'docx' => ['fa' => 'fas fa-file-word', 'label' => 'DOC', 'tone' => 'blue'],
            'ppt' => ['fa' => 'fas fa-file-powerpoint', 'label' => 'PPT', 'tone' => 'orange'],
            'pptx' => ['fa' => 'fas fa-file-powerpoint', 'label' => 'PPT', 'tone' => 'orange'],
            'xls' => ['fa' => 'fas fa-file-excel', 'label' => 'XLS', 'tone' => 'green'],
            'xlsx' => ['fa' => 'fas fa-file-excel', 'label' => 'XLS', 'tone' => 'green'],
            'zip' => ['fa' => 'fas fa-file-archive', 'label' => 'ZIP', 'tone' => 'purple'],
            'rar' => ['fa' => 'fas fa-file-archive', 'label' => 'RAR', 'tone' => 'purple'],
            'html' => ['fa' => 'fas fa-code', 'label' => 'HTML', 'tone' => 'purple'],
            'htm' => ['fa' => 'fas fa-code', 'label' => 'HTML', 'tone' => 'purple'],
            'png' => ['fa' => 'fas fa-file-image', 'label' => 'IMG', 'tone' => 'blue'],
            'jpg' => ['fa' => 'fas fa-file-image', 'label' => 'IMG', 'tone' => 'blue'],
            'jpeg' => ['fa' => 'fas fa-file-image', 'label' => 'IMG', 'tone' => 'blue'],
            'mp3' => ['fa' => 'fas fa-file-audio', 'label' => 'AUD', 'tone' => 'orange'],
            'mp4' => ['fa' => 'fas fa-file-video', 'label' => 'VID', 'tone' => 'purple'],
        ];

        return $map[$ext] ?? ['fa' => 'fas fa-file-alt', 'label' => strtoupper($ext ?: 'FILE'), 'tone' => 'blue'];
    };

    $typeChips = [
        'all' => __('student_timeline.materials_filter_all'),
        'pdf' => 'PDF',
        'doc' => __('student_timeline.materials_filter_doc'),
        'ppt' => __('student_timeline.materials_filter_ppt'),
        'sheet' => __('student_timeline.materials_filter_sheet'),
        'zip' => __('student_timeline.materials_filter_zip'),
        'html' => __('student_timeline.materials_filter_html'),
        'image' => __('student_timeline.materials_filter_image'),
        'audio' => __('student_timeline.materials_filter_audio'),
        'video' => __('student_timeline.materials_filter_video'),
        'other' => __('student_timeline.materials_filter_other'),
    ];

    $folderLabel = null;
    if ($activeFolder) {
        $folderLabel = ($activeFolder->is_uncategorized ?? false)
            ? __('student_timeline.folder_uncategorized')
            : $activeFolder->displayName($locale);
    }

    $filterBase = array_filter([
        'q' => $searchQuery ?: null,
        'course' => (! $activeFolder && $courseId) ? $courseId : null,
        'lecture' => (! $activeFolder && $lectureId) ? $lectureId : null,
        'folder' => $activeFolder
            ? (($activeFolder->is_uncategorized ?? false) ? 'none' : ($activeFolder->slug ?: $activeFolder->id))
            : null,
        'theme' => $themeFilter ?: null,
        'sort' => $sort !== 'newest' ? $sort : null,
        'lang' => request('lang') ?: null,
    ], fn ($v) => $v !== null && $v !== '');

    $crumbs = [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.family_library_title'), 'url' => route('student.library.home')],
        ['label' => __('student_timeline.nav_library_materials'), 'url' => ($activeFolder || $themeFilter) ? route('student.library.materials') : null],
    ];
    if ($themeFilter && isset($familyThemes[$themeFilter])) {
        $crumbs[] = ['label' => $locale === 'en' ? $familyThemes[$themeFilter]['en'] : $familyThemes[$themeFilter]['ar'], 'url' => null];
    }
    if ($folderLabel) {
        $crumbs[] = ['label' => $folderLabel, 'url' => null];
    }
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_library_materials'),
    'crumbs' => $crumbs,
    'toolbarView' => 'student.library._materials-toolbar',
    'toolbarData' => [
        'searchQuery' => $searchQuery,
        'materialCount' => $materialCount,
        'courseId' => $courseId,
        'lectureId' => $lectureId,
        'typeFilter' => $typeFilter,
        'sort' => $sort,
        'courses' => $courses,
        'lectures' => $lectures,
        'activeFolder' => $activeFolder,
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-msg-intro">
    <div>
        <h2>{{ $folderLabel ?: __('student_timeline.materials_title') }}</h2>
        <p>{{ $activeFolder && !($activeFolder->is_uncategorized ?? false) && method_exists($activeFolder, 'displayDescription') && $activeFolder->displayDescription($locale)
            ? $activeFolder->displayDescription($locale)
            : __('student_timeline.family_materials_hint') }}</p>
    </div>
    <div class="st-msg-intro__actions">
        @if(Route::has('student.library.home'))
            <a href="{{ route('student.library.home') }}" class="st-pill st-pill--outline">{{ __('student_timeline.family_library_title') }}</a>
        @endif
        @if(Route::has('student.library.videos'))
            <a href="{{ route('student.library.videos') }}" class="st-pill st-pill--outline">{{ __('student_timeline.nav_library_videos') }}</a>
        @endif
    </div>
</section>

@if(! $hasLibraryEntitlement)
    <div class="st-lib-package-banner" role="status">
        <div>
            <strong>{{ __('student_timeline.lib_need_package_title') }}</strong>
            <p>{{ __('student_timeline.lib_need_package_materials') }}</p>
        </div>
        <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.lib_browse_packages') }}</a>
    </div>
@endif

@if(! $activeFolder && ($academyFolderCount > 0 || $teacherFolderCount > 0 || $linkedTeacherCount > 0))
    <div class="st-lib-source-strip" aria-label="{{ __('student_timeline.lib_sources_title') }}">
        <div class="st-lib-source-chip st-lib-source-chip--academy">
            <i class="fas fa-university" aria-hidden="true"></i>
            <div>
                <strong>{{ __('student_timeline.lib_academy_title') }}</strong>
                <span>{{ trans_choice('student_timeline.lib_folders_count', $academyFolderCount, ['count' => $academyFolderCount]) }}</span>
            </div>
        </div>
        <div class="st-lib-source-chip st-lib-source-chip--teacher">
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
            <div>
                <strong>{{ __('student_timeline.lib_teachers_title') }}</strong>
                <span>{{ trans_choice('student_timeline.lib_folders_count', $teacherFolderCount, ['count' => $teacherFolderCount]) }}</span>
            </div>
        </div>
    </div>
@endif

@if(!empty($familyThemes))
    <section class="st-theme-strip" aria-label="{{ __('student_timeline.family_themes') }}">
        <a href="{{ route('student.library.materials', array_filter(['q' => $searchQuery ?: null, 'folder' => $activeFolder ? (($activeFolder->is_uncategorized ?? false) ? 'none' : ($activeFolder->slug ?: $activeFolder->id)) : null])) }}"
           class="st-theme-chip {{ $themeFilter === '' ? 'is-active' : '' }}">{{ __('student_timeline.materials_filter_all') }}</a>
        @foreach(['books','presentations','html','games','general'] as $themeKey)
            @continue(!isset($familyThemes[$themeKey]))
            @php $meta = $familyThemes[$themeKey]; @endphp
            <a href="{{ route('student.library.materials', array_merge($filterBase, ['theme' => $themeKey])) }}"
               class="st-theme-chip st-theme-chip--{{ $meta['tone'] }} {{ $themeFilter === $themeKey ? 'is-active' : '' }}">
                <i class="{{ $meta['icon'] }}" aria-hidden="true"></i>
                {{ $locale === 'en' ? $meta['en'] : $meta['ar'] }}
            </a>
        @endforeach
    </section>
@endif

@if($libraryFolders->isNotEmpty() || $uncategorizedCount > 0)
    <section class="st-folder-grid" aria-label="{{ __('student_timeline.folders_title') }}">
        <a href="{{ route('student.library.materials', array_filter(['q' => $searchQuery ?: null])) }}"
           class="st-folder-card st-folder-card--all {{ ! $activeFolder ? 'is-active' : '' }}">
            <span class="st-folder-card__icon"><i class="fas fa-th-large" aria-hidden="true"></i></span>
            <span class="st-folder-card__body">
                <strong>{{ __('student_timeline.folder_all_materials') }}</strong>
                <em>{{ __('student_timeline.folder_all_materials_hint') }}</em>
            </span>
        </a>

        @foreach($libraryFolders as $i => $folder)
            @php $tone = $folder->color ?: $tones[$i % count($tones)]; @endphp
            <a href="{{ route('student.library.materials', array_filter(['folder' => $folder->slug ?: $folder->id, 'q' => $searchQuery ?: null])) }}"
               class="st-folder-card st-folder-card--{{ $tone }} {{ ($activeFolder && !($activeFolder->is_uncategorized ?? false) && (int) $activeFolder->id === (int) $folder->id) ? 'is-active' : '' }}">
                <span class="st-folder-card__icon"><i class="{{ $folder->icon ?: 'fas fa-folder' }}" aria-hidden="true"></i></span>
                <span class="st-folder-card__body">
                    <strong>{{ $folder->displayName($locale) }}</strong>
                    <em>
                        {{ $folder->academicYear->name ?? '' }}
                        @if($folder->instructor_id)
                            · <span class="st-lib-badge st-lib-badge--teacher">{{ __('student_timeline.lib_badge_teacher') }}{{ $folder->instructor?->name ? ' · '.$folder->instructor->name : '' }}</span>
                        @else
                            · <span class="st-lib-badge st-lib-badge--academy">{{ __('student_timeline.lib_badge_academy') }}</span>
                        @endif
                        · {{ trans_choice('student_timeline.folder_files_count', (int) $folder->materials_count, ['count' => (int) $folder->materials_count]) }}
                    </em>
                </span>
            </a>
        @endforeach

        @if($uncategorizedCount > 0)
            <a href="{{ route('student.library.materials', array_filter(['folder' => 'none', 'q' => $searchQuery ?: null])) }}"
               class="st-folder-card st-folder-card--purple {{ ($activeFolder->is_uncategorized ?? false) ? 'is-active' : '' }}">
                <span class="st-folder-card__icon"><i class="fas fa-inbox" aria-hidden="true"></i></span>
                <span class="st-folder-card__body">
                    <strong>{{ __('student_timeline.folder_uncategorized') }}</strong>
                    <em>{{ trans_choice('student_timeline.folder_files_count', $uncategorizedCount, ['count' => $uncategorizedCount]) }}</em>
                </span>
            </a>
        @endif
    </section>
@endif

<section class="st-mat-shell" aria-label="{{ __('student_timeline.materials_title') }}">
    <div class="st-mat-filters">
        <div class="st-mat-filters__row" role="tablist" aria-label="{{ __('student_timeline.materials_filter_type') }}">
            @foreach($typeChips as $key => $label)
                @php
                    $count = (int) ($typeCounts[$key] ?? 0);
                    $chipUrl = route('student.library.materials', array_filter($filterBase + ['type' => $key !== 'all' ? $key : null]));
                @endphp
                @if($key === 'all' || $count > 0 || $typeFilter === $key)
                    <a href="{{ $chipUrl }}"
                       class="st-mat-chip {{ $typeFilter === $key ? 'is-active' : '' }}"
                       role="tab"
                       aria-selected="{{ $typeFilter === $key ? 'true' : 'false' }}">
                        <span>{{ $label }}</span>
                        @if($key === 'all' || $count > 0)
                            <em>{{ $count }}</em>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>

        @if(! $activeFolder || ($activeFolder->is_uncategorized ?? false))
        <form class="st-mat-filters__form" method="get" action="{{ route('student.library.materials') }}">
            @if(request('lang'))
                <input type="hidden" name="lang" value="{{ request('lang') }}">
            @endif
            @if($searchQuery !== '')
                <input type="hidden" name="q" value="{{ $searchQuery }}">
            @endif
            @if($typeFilter !== 'all')
                <input type="hidden" name="type" value="{{ $typeFilter }}">
            @endif
            @if($activeFolder && ($activeFolder->is_uncategorized ?? false))
                <input type="hidden" name="folder" value="none">
            @endif

            <label class="st-mat-select">
                <span>{{ __('student_timeline.materials_filter_course') }}</span>
                <select name="course" onchange="this.form.submit()">
                    <option value="">{{ __('student_timeline.materials_filter_course_all') }}</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($courseId === (int) $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </label>

            <label class="st-mat-select">
                <span>{{ __('student_timeline.materials_filter_lecture') }}</span>
                <select name="lecture" onchange="this.form.submit()">
                    <option value="">{{ __('student_timeline.materials_filter_lecture_all') }}</option>
                    @foreach($lectures as $lecture)
                        <option value="{{ $lecture->id }}" @selected($lectureId === (int) $lecture->id)>{{ $lecture->title }}</option>
                    @endforeach
                </select>
            </label>

            <label class="st-mat-select">
                <span>{{ __('student_timeline.materials_filter_sort') }}</span>
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest" @selected($sort === 'newest')>{{ __('student_timeline.materials_sort_newest') }}</option>
                    <option value="oldest" @selected($sort === 'oldest')>{{ __('student_timeline.materials_sort_oldest') }}</option>
                    <option value="title" @selected($sort === 'title')>{{ __('student_timeline.materials_sort_title') }}</option>
                    <option value="lecture" @selected($sort === 'lecture')>{{ __('student_timeline.materials_sort_lecture') }}</option>
                </select>
            </label>

            @if($courseId || $lectureId || $typeFilter !== 'all' || $searchQuery !== '' || $activeFolder)
                <a href="{{ route('student.library.materials') }}" class="st-mat-reset">{{ __('student_timeline.materials_clear_filters') }}</a>
            @endif
        </form>
        @endif
    </div>

    @if($materialsEmpty)
        <div class="st-empty-panel">
            @if(! $hasLibraryEntitlement && $teacherFolderCount === 0 && ! $hasFilters)
                <h3>{{ __('student_timeline.lib_need_package_title') }}</h3>
                <p>{{ __('student_timeline.lib_need_package_materials') }}</p>
                <div class="st-biz-banner__actions">
                    <a href="{{ $packagesUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.lib_browse_packages') }}</a>
                    @if($linkedTeacherCount === 0)
                        <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
                    @endif
                </div>
            @elseif($hasLibraryEntitlement && $academyFolderCount === 0 && $teacherFolderCount === 0 && ! $hasFilters)
                <h3>{{ __('student_timeline.lib_academy_empty_title') }}</h3>
                <p>{{ __('student_timeline.lib_academy_empty_hint') }}</p>
            @elseif($linkedTeacherCount > 0 && $teacherFolderCount === 0 && $academyFolderCount === 0 && ! $hasFilters)
                <h3>{{ __('student_timeline.lib_teacher_empty_title') }}</h3>
                <p>{{ __('student_timeline.lib_teacher_empty_hint') }}</p>
            @else
                <h3>{{ __('student_timeline.no_materials') }}</h3>
                <p>{{ __('student_timeline.no_materials_hint') }}</p>
                <div class="st-biz-banner__actions">
                    @if($hasFilters)
                        <a href="{{ route('student.library.materials') }}" class="st-pill st-pill--solid">{{ __('student_timeline.materials_clear_filters') }}</a>
                    @endif
                    @if(! $hasLibraryEntitlement)
                        <a href="{{ $packagesUrl }}" class="st-pill st-pill--outline">{{ __('student_timeline.lib_browse_packages') }}</a>
                    @endif
                    @if(Route::has('student.classes.index'))
                        <a href="{{ (Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard')) }}" class="st-pill st-pill--outline">{{ __('student_timeline.my_classes') }}</a>
                    @endif
                </div>
            @endif
        </div>
    @else
        @php
            $renderMaterialCard = function ($material, $i) use ($fileMeta, $tones, $locale) {
                $meta = $fileMeta($material);
                $tone = $meta['tone'] ?? $tones[$i % count($tones)];
                $title = $material->title ?: $material->file_name ?: __('student_timeline.material_item');
                $experienceUrl = method_exists($material, 'experienceUrl') ? $material->experienceUrl() : null;
                $downloadUrl = $material->downloadUrl();
                $playable = $experienceUrl && $experienceUrl !== $downloadUrl;
                $courseTitle = $material->lecture?->course?->title;
                $lectureTitle = $material->lecture?->title;
                $folderTitle = $material->folder?->displayName($locale);
                $teacherName = $material->folder?->instructor?->name;
                $isTeacher = (bool) ($material->folder?->instructor_id);

                return compact('meta', 'tone', 'title', 'experienceUrl', 'downloadUrl', 'playable', 'courseTitle', 'lectureTitle', 'folderTitle', 'teacherName', 'isTeacher');
            };
        @endphp

        @if($showSections && $academyMaterials->isNotEmpty())
            <div class="st-lib-section-head">
                <h3><i class="fas fa-university" aria-hidden="true"></i> {{ __('student_timeline.lib_academy_title') }}</h3>
                <p>{{ __('student_timeline.lib_academy_section_hint') }}</p>
            </div>
            <div class="st-mat-grid" role="list">
                @foreach($academyMaterials as $i => $material)
                    @php extract($renderMaterialCard($material, $i)); @endphp
                    @include('student.library._material-card', compact('material', 'meta', 'tone', 'title', 'experienceUrl', 'downloadUrl', 'playable', 'courseTitle', 'lectureTitle', 'folderTitle', 'teacherName', 'isTeacher', 'locale'))
                @endforeach
            </div>
        @endif

        @if($showSections && $teacherMaterials->isNotEmpty())
            <div class="st-lib-section-head">
                <h3><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> {{ __('student_timeline.lib_teachers_title') }}</h3>
                <p>{{ __('student_timeline.lib_teachers_section_hint') }}</p>
            </div>
            <div class="st-mat-grid" role="list">
                @foreach($teacherMaterials as $i => $material)
                    @php extract($renderMaterialCard($material, $i)); @endphp
                    @include('student.library._material-card', compact('material', 'meta', 'tone', 'title', 'experienceUrl', 'downloadUrl', 'playable', 'courseTitle', 'lectureTitle', 'folderTitle', 'teacherName', 'isTeacher', 'locale'))
                @endforeach
            </div>
        @endif

        @if(! $showSections)
            <div class="st-mat-grid" role="list">
                @foreach($materials as $i => $material)
                    @php extract($renderMaterialCard($material, $i)); @endphp
                    @include('student.library._material-card', compact('material', 'meta', 'tone', 'title', 'experienceUrl', 'downloadUrl', 'playable', 'courseTitle', 'lectureTitle', 'folderTitle', 'teacherName', 'isTeacher', 'locale'))
                @endforeach
            </div>
        @endif

        @if(method_exists($materials, 'hasPages') && $materials->hasPages())
            <div class="st-pager">
                {{ $materials->links() }}
            </div>
        @endif
    @endif
</section>
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.library_links') }}</h2>
</div>

@if(Route::has('student.library.videos'))
    <a href="{{ route('student.library.videos') }}" class="st-event-card st-event-card--blue">
        <h3>{{ __('student_timeline.nav_library_videos') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.videos_side_hint') }}</p>
    </a>
@endif

@if(! $hasLibraryEntitlement)
    <a href="{{ $packagesUrl }}" class="st-event-card st-event-card--orange">
        <h3>{{ __('student_timeline.lib_browse_packages') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.lib_need_package_hint') }}</p>
    </a>
@endif

@if(Route::has('student.lectures.index'))
    <a href="{{ route('student.lectures.index') }}" class="st-event-card st-event-card--orange">
        <h3>{{ __('student_timeline.nav_lectures') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.lectures_side_hint') }}</p>
    </a>
@endif

@if(Route::has('student.assignments.index'))
    <a href="{{ route('student.assignments.index') }}" class="st-event-card st-event-card--pink">
        <h3>{{ __('student_timeline.nav_assignments') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.assignments_side_hint') }}</p>
    </a>
@endif
@endsection
