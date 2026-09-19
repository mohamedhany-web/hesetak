<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LectureMaterial;
use App\Models\LibraryFolder;
use App\Models\OneToOneSession;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Services\LectureMaterialStorage;
use App\Services\LibraryFolderAccessService;
use App\Services\StudentScheduleService;
use App\Support\FamilyLibraryThemes;
use App\Helpers\VideoHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentHomeExtrasController extends Controller
{
    private function denyLegacyLibraries(): ?RedirectResponse
    {
        if (student_ui('show_libraries')) {
            return null;
        }

        return redirect()->route('dashboard');
    }

    public function join(Request $request, string $type, int $id): RedirectResponse
    {
        $url = StudentScheduleService::resolveJoinUrl($request->user(), $type, $id);

        if (! $url) {
            return back()->with('error', 'لا يمكن دخول هذه الحصة الآن. تأكد من الاشتراك أو أن الغرفة جاهزة.');
        }

        return redirect()->away($url);
    }

    /**
     * بوابة المكتبة الآمنة — كتب، ألعاب، HTML، أطفال، إسلامي.
     */
    public function libraryHome(Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyLegacyLibraries()) {
            return $deny;
        }

        $locale = app()->getLocale();
        $user = $request->user();
        $themes = FamilyLibraryThemes::all();

        $hasLibraryEntitlement = LibraryFolderAccessService::hasAnyLibraryEntitlement($user);
        $teacherIds = \App\Services\StudentTeacherLinkService::instructorIdsForStudent($user);
        $linkedTeacherCount = count($teacherIds);

        $academyFolderCount = 0;
        $teacherFolderCount = 0;
        $academyMaterialCount = 0;
        $teacherMaterialCount = 0;
        $academyVideoCount = 0;
        $teacherVideoCount = 0;
        $curriculumCourseCount = 0;
        $manahijItemCount = 0;

        if (Schema::hasTable('library_folders')) {
            $materialFolders = LibraryFolderAccessService::foldersVisibleTo($user, LibraryFolder::KIND_MATERIALS)->get(['id', 'instructor_id']);
            $academyFolderCount = $materialFolders->whereNull('instructor_id')->count();
            $teacherFolderCount = $materialFolders->whereNotNull('instructor_id')->count();

            if (Schema::hasTable('lecture_materials') && $materialFolders->isNotEmpty()) {
                $academyIds = $materialFolders->whereNull('instructor_id')->pluck('id');
                $teacherFolderIds = $materialFolders->whereNotNull('instructor_id')->pluck('id');
                $visible = fn ($q) => $q->where(function ($v) {
                    $v->where('is_visible_to_student', true)->orWhereNull('is_visible_to_student');
                });
                if ($academyIds->isNotEmpty()) {
                    $academyMaterialCount = LectureMaterial::query()->where($visible)->whereIn('library_folder_id', $academyIds)->count();
                }
                if ($teacherFolderIds->isNotEmpty()) {
                    $teacherMaterialCount = LectureMaterial::query()->where($visible)->whereIn('library_folder_id', $teacherFolderIds)->count();
                }
            }
        }

        if (Schema::hasTable('library_videos')) {
            $videosBase = LibraryFolderAccessService::videosVisibleTo($user);
            $academyVideoCount = (clone $videosBase)->where(function ($query) {
                $query->where('audience', \App\Models\LibraryVideo::AUDIENCE_GENERAL)
                    ->orWhereNull('audience');
            })->count();
            $teacherVideoCount = (clone $videosBase)->where('audience', \App\Models\LibraryVideo::AUDIENCE_TEACHER_STUDENTS)->count();
        }

        if (Schema::hasTable('student_course_enrollments') && Schema::hasTable('advanced_courses')) {
            try {
                $curriculumCourseCount = $user->activeCourses()->count();
            } catch (\Throwable) {
                $curriculumCourseCount = DB::table('student_course_enrollments')
                    ->where('user_id', $user->id)
                    ->when(Schema::hasColumn('student_course_enrollments', 'status'), fn ($q) => $q->where('status', 'active'))
                    ->count();
            }
        }

        if (Schema::hasTable('curriculum_library_items')) {
            $manahijItemCount = \App\Models\CurriculumLibraryItem::active()
                ->where(function ($qry) use ($user) {
                    $qry->whereNull('curriculum_library_items.category_id')
                        ->orWhereHas('category', fn ($cq) => $cq->accessibleByStudent($user));
                })
                ->count();
        }

        $materialThemes = [
            FamilyLibraryThemes::BOOKS,
            FamilyLibraryThemes::PRESENTATIONS,
            FamilyLibraryThemes::HTML,
            FamilyLibraryThemes::GAMES,
            FamilyLibraryThemes::GENERAL,
        ];
        $videoThemes = [
            FamilyLibraryThemes::KIDS,
            FamilyLibraryThemes::ISLAMIC,
            FamilyLibraryThemes::GENERAL,
        ];

        $sections = [];
        foreach ($materialThemes as $key) {
            $meta = $themes[$key];
            $sections[] = [
                'key' => $key,
                'kind' => 'materials',
                'label' => $locale === 'en' ? $meta['en'] : $meta['ar'],
                'hint' => $locale === 'en' ? $meta['hint_en'] : $meta['hint_ar'],
                'icon' => $meta['icon'],
                'tone' => $meta['tone'],
                'url' => route('student.library.materials', ['theme' => $key]),
            ];
        }
        foreach ($videoThemes as $key) {
            $meta = $themes[$key];
            $sections[] = [
                'key' => $key,
                'kind' => 'videos',
                'label' => $locale === 'en' ? $meta['en'] : $meta['ar'],
                'hint' => $locale === 'en' ? $meta['hint_en'] : $meta['hint_ar'],
                'icon' => $meta['icon'],
                'tone' => $meta['tone'],
                'url' => route('student.library.videos', ['theme' => $key]),
            ];
        }

        return view('student.library.home', [
            'sections' => $sections,
            'hasLibraryEntitlement' => $hasLibraryEntitlement,
            'linkedTeacherCount' => $linkedTeacherCount,
            'academyFolderCount' => $academyFolderCount,
            'teacherFolderCount' => $teacherFolderCount,
            'academyMaterialCount' => $academyMaterialCount,
            'teacherMaterialCount' => $teacherMaterialCount,
            'academyVideoCount' => $academyVideoCount,
            'teacherVideoCount' => $teacherVideoCount,
            'curriculumCourseCount' => $curriculumCourseCount,
            'manahijItemCount' => $manahijItemCount,
            'packagesUrl' => Route::has('public.service-packages.index')
                ? route('public.service-packages.index')
                : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard')),
        ]);
    }

    /**
     * مكتبة الملفات الموحّدة — ماتريال الأكاديمية/المعلمين + المناهج التفاعلية.
     */
    public function files(Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyLegacyLibraries()) {
            return $deny;
        }

        $user = $request->user();
        $locale = app()->getLocale();
        $tab = strtolower(trim((string) $request->query('tab', 'all')));
        if (! in_array($tab, ['all', 'materials', 'manahij'], true)) {
            $tab = 'all';
        }
        $q = trim((string) $request->query('q', ''));

        $hasLibraryEntitlement = LibraryFolderAccessService::hasAnyLibraryEntitlement($user);
        $packagesUrl = Route::has('public.service-packages.index')
            ? route('public.service-packages.index')
            : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard'));

        $materialCards = collect();
        if (in_array($tab, ['all', 'materials'], true) && Schema::hasTable('lecture_materials')) {
            try {
                $folderIds = collect();
                if (Schema::hasTable('library_folders')) {
                    $folderIds = LibraryFolderAccessService::foldersVisibleTo($user, LibraryFolder::KIND_MATERIALS)->pluck('id');
                }
                $materialsQuery = LectureMaterial::query()
                    ->with(['folder:id,name_ar,name_en,instructor_id', 'lecture:id,title'])
                    ->where(function ($v) {
                        $v->where('is_visible_to_student', true)->orWhereNull('is_visible_to_student');
                    })
                    ->where(function ($scope) use ($folderIds) {
                        if ($folderIds->isNotEmpty()) {
                            $scope->whereIn('library_folder_id', $folderIds);
                        } else {
                            $scope->whereRaw('1 = 0');
                        }
                    });
                if ($q !== '') {
                    $materialsQuery->where(function ($qry) use ($q) {
                        $qry->where('title', 'like', "%{$q}%")
                            ->orWhere('file_name', 'like', "%{$q}%");
                    });
                }
                $materialCards = $materialsQuery->latest('id')->limit($tab === 'materials' ? 48 : 12)->get()->map(function (LectureMaterial $m) {
                    return [
                        'source' => 'materials',
                        'title' => $m->title ?: ($m->file_name ?: 'ملف'),
                        'meta' => $m->folder?->displayName(app()->getLocale()) ?: ($m->lecture?->title ?: ''),
                        'badge' => $m->folder?->instructor_id ? 'teacher' : 'academy',
                        'url' => route('student.library.materials.experience', $m),
                        'icon' => 'fas fa-file-alt',
                        'locked' => false,
                    ];
                });
            } catch (\Throwable) {
                $materialCards = collect();
            }
        }

        $manahijCards = collect();
        if (in_array($tab, ['all', 'manahij'], true) && Schema::hasTable('curriculum_library_items')) {
            try {
                $usedFreePreview = Schema::hasTable('curriculum_library_preview_opens')
                    ? \App\Models\CurriculumLibraryPreviewOpen::hasUsedFreePreview($user->id)
                    : false;
                $itemsQuery = \App\Models\CurriculumLibraryItem::active()
                    ->with('category')
                    ->ordered()
                    ->where(function ($qry) use ($user) {
                        $qry->whereNull('curriculum_library_items.category_id')
                            ->orWhereHas('category', fn ($cq) => $cq->accessibleByStudent($user));
                    });
                if ($q !== '') {
                    $itemsQuery->where(function ($qry) use ($q) {
                        $qry->where('title', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%")
                            ->orWhere('subject', 'like', "%{$q}%");
                    });
                }
                $manahijCards = $itemsQuery->limit($tab === 'manahij' ? 48 : 12)->get()->map(function ($item) use ($hasLibraryEntitlement, $usedFreePreview, $packagesUrl) {
                    $locked = (! $hasLibraryEntitlement) && $usedFreePreview;

                    return [
                        'source' => 'manahij',
                        'title' => $item->title,
                        'meta' => trim(($item->category->name ?? '').($item->subject ? ' · '.$item->subject : '')),
                        'badge' => 'manahij',
                        'url' => $locked ? $packagesUrl : route('curriculum-library.show', $item),
                        'icon' => 'fas fa-chalkboard',
                        'locked' => $locked,
                    ];
                });
            } catch (\Throwable) {
                $manahijCards = collect();
            }
        }

        $cards = $materialCards->concat($manahijCards)->values();

        return view('student.library.files', [
            'locale' => $locale,
            'tab' => $tab,
            'searchQuery' => $q,
            'cards' => $cards,
            'materialCount' => $materialCards->count(),
            'manahijCount' => $manahijCards->count(),
            'hasLibraryEntitlement' => $hasLibraryEntitlement,
            'packagesUrl' => $packagesUrl,
        ]);
    }

    public function materials(Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyLegacyLibraries()) {
            return $deny;
        }

        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $courseId = (int) $request->query('course', 0);
        $lectureId = (int) $request->query('lecture', 0);
        $folderParam = $request->query('folder');
        $type = strtolower(trim((string) $request->query('type', 'all')));
        $sort = (string) $request->query('sort', 'newest');
        $theme = strtolower(trim((string) $request->query('theme', '')));
        if ($theme !== '' && ! in_array($theme, FamilyLibraryThemes::keys(), true)) {
            $theme = '';
        }

        $allowedTypes = ['all', 'pdf', 'doc', 'ppt', 'sheet', 'zip', 'html', 'image', 'audio', 'video', 'other'];
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }
        if (! in_array($sort, ['newest', 'oldest', 'title', 'lecture'], true)) {
            $sort = 'newest';
        }

        $materials = LectureMaterial::query()->whereRaw('1=0')->paginate(24);
        $courses = collect();
        $lectures = collect();
        $typeCounts = [];
        $libraryFolders = collect();
        $activeFolder = null;
        $uncategorizedCount = 0;

        $folderIds = collect();
        if (Schema::hasTable('library_folders') && Schema::hasColumn('library_folders', 'kind')) {
            $libraryFolders = LibraryFolderAccessService::foldersVisibleTo($user, LibraryFolder::KIND_MATERIALS)
                ->with(['academicYear:id,name', 'instructor:id,name'])
                ->withCount(['materials' => function ($query) {
                    $query->where(function ($q) {
                        $q->where('is_visible_to_student', true)->orWhereNull('is_visible_to_student');
                    });
                }])
                ->get();
            $folderIds = $libraryFolders->pluck('id');

            if ($folderParam === 'none') {
                $activeFolder = (object) [
                    'id' => 'none',
                    'slug' => 'none',
                    'is_uncategorized' => true,
                ];
            } elseif (filled($folderParam)) {
                $activeFolder = LibraryFolderAccessService::resolveFolderFromParam($folderParam);
                abort_unless($activeFolder && $folderIds->contains((int) $activeFolder->id), 403, 'يلزم اشتراك باقة المكتبات لهذه السنة.');
                abort_unless(LibraryFolderAccessService::canAccessFolder($user, $activeFolder), 403, 'يلزم اشتراك باقة المكتبات لهذه السنة.');
            }
        }

        if (Schema::hasTable('lecture_materials')) {
            $courseIds = collect();
            $allLectureIds = collect();

            if (Schema::hasTable('student_course_enrollments')) {
                $courseIds = DB::table('student_course_enrollments')
                    ->where('user_id', $user->id)
                    ->when(Schema::hasColumn('student_course_enrollments', 'status'), fn ($query) => $query->where('status', 'active'))
                    ->pluck('advanced_course_id');

                if ($courseIds->isNotEmpty() && Schema::hasTable('lectures')) {
                    $allLectureIds = Lecture::query()->whereIn('course_id', $courseIds)->pluck('id');

                    $courses = \App\Models\AdvancedCourse::query()
                        ->whereIn('id', $courseIds)
                        ->orderBy('title')
                        ->get(['id', 'title']);

                    $lectures = Lecture::query()
                        ->whereIn('course_id', $courseIds)
                        ->when($courseId > 0, fn ($query) => $query->where('course_id', $courseId))
                        ->orderBy('title')
                        ->get(['id', 'title', 'course_id']);

                    if ($lectureId > 0 && ! $lectures->contains('id', $lectureId)) {
                        $lectureId = 0;
                    }
                }
            }

            $uncategorizedCount = $allLectureIds->isNotEmpty()
                ? LectureMaterial::query()
                    ->where(function ($query) {
                        $query->where('is_visible_to_student', true)->orWhereNull('is_visible_to_student');
                    })
                    ->whereIn('lecture_id', $allLectureIds)
                    ->whereNull('library_folder_id')
                    ->count()
                : 0;

            if ($allLectureIds->isNotEmpty() || $folderIds->isNotEmpty()) {
                $scopeMaterials = function ($query) use ($allLectureIds, $folderIds, $activeFolder, $courseId, $lectureId) {
                    $query->where(function ($visible) {
                        $visible->where('is_visible_to_student', true)->orWhereNull('is_visible_to_student');
                    });

                    if ($activeFolder && ($activeFolder->is_uncategorized ?? false)) {
                        $query->whereIn('lecture_id', $allLectureIds)->whereNull('library_folder_id');
                    } elseif ($activeFolder) {
                        $query->where('library_folder_id', $activeFolder->id);
                    } else {
                        $query->where(function ($inner) use ($allLectureIds, $folderIds) {
                            if ($allLectureIds->isNotEmpty()) {
                                $inner->whereIn('lecture_id', $allLectureIds);
                            }
                            if ($folderIds->isNotEmpty()) {
                                $method = $allLectureIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                                $inner->{$method}('library_folder_id', $folderIds);
                            }
                        });
                    }

                    if (! $activeFolder || ($activeFolder->is_uncategorized ?? false)) {
                        if ($courseId > 0) {
                            $query->whereHas('lecture', fn ($lq) => $lq->where('course_id', $courseId));
                        }
                        if ($lectureId > 0) {
                            $query->where('lecture_id', $lectureId);
                        }
                    }
                };

                $base = LectureMaterial::query()->where($scopeMaterials)
                    ->when($theme !== '' && Schema::hasColumn('lecture_materials', 'content_theme'), function ($query) use ($theme) {
                        $query->where('content_theme', $theme);
                    });
                $typeCounts = $this->materialTypeCounts((clone $base)->get(['file_name', 'file_path']));

                $materialsQuery = LectureMaterial::query()
                    ->with([
                        'lecture:id,title,course_id',
                        'lecture.course:id,title',
                        'folder:id,name_ar,name_en,academic_year_id,instructor_id',
                        'folder.academicYear:id,name',
                        'folder.instructor:id,name',
                    ])
                    ->where($scopeMaterials)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($inner) use ($q) {
                            $inner->where('title', 'like', '%'.$q.'%')
                                ->orWhere('file_name', 'like', '%'.$q.'%')
                                ->orWhereHas('folder', function ($fq) use ($q) {
                                    $fq->where('name_ar', 'like', '%'.$q.'%')
                                        ->orWhere('name_en', 'like', '%'.$q.'%');
                                })
                                ->orWhereHas('lecture', function ($lq) use ($q) {
                                    $lq->where('title', 'like', '%'.$q.'%')
                                        ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', '%'.$q.'%'));
                                });
                        });
                    })
                    ->when($theme !== '' && Schema::hasColumn('lecture_materials', 'content_theme'), function ($query) use ($theme) {
                        $query->where('content_theme', $theme);
                    });

                if ($type !== 'all') {
                    $exts = $this->materialTypeExtensions($type);
                    if ($type === 'other') {
                        $known = ['pdf', 'doc', 'docx', 'docm', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'html', 'htm', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'mp3', 'wav', 'm4a', 'mp4', 'mov', 'webm'];
                        $materialsQuery->where(function ($query) use ($known) {
                            foreach ($known as $ext) {
                                $query->where(function ($inner) use ($ext) {
                                    $inner->where(function ($w) use ($ext) {
                                        $w->whereNull('file_name')->orWhere('file_name', 'not like', '%.'.$ext);
                                    })->where(function ($w) use ($ext) {
                                        $w->whereNull('file_path')->orWhere('file_path', 'not like', '%.'.$ext);
                                    });
                                });
                            }
                        });
                    } elseif ($exts !== []) {
                        $materialsQuery->where(function ($query) use ($exts) {
                            foreach ($exts as $ext) {
                                $query->orWhere('file_name', 'like', '%.'.$ext)
                                    ->orWhere('file_path', 'like', '%.'.$ext);
                            }
                        });
                    }
                }

                match ($sort) {
                    'oldest' => $materialsQuery->orderBy('id'),
                    'title' => $materialsQuery->orderByRaw('COALESCE(NULLIF(title, ""), file_name) asc'),
                    'lecture' => $materialsQuery->orderBy('lecture_id')->orderBy('sort_order'),
                    default => $materialsQuery->orderBy('sort_order')->latest('id'),
                };

                $materials = $materialsQuery->paginate(24)->withQueryString();
            }
        }

        $hasLibraryEntitlement = LibraryFolderAccessService::hasAnyLibraryEntitlement($user);
        $linkedTeacherCount = count(\App\Services\StudentTeacherLinkService::instructorIdsForStudent($user));
        $academyFolders = $libraryFolders->whereNull('instructor_id')->values();
        $teacherFolders = $libraryFolders->whereNotNull('instructor_id')->values();
        $academyFolderCount = $academyFolders->count();
        $teacherFolderCount = $teacherFolders->count();

        return view('student.library.materials', [
            'materials' => $materials,
            'searchQuery' => $q,
            'courseId' => $courseId,
            'lectureId' => $lectureId,
            'typeFilter' => $type,
            'themeFilter' => $theme,
            'sort' => $sort,
            'courses' => $courses,
            'lectures' => $lectures,
            'typeCounts' => $typeCounts,
            'libraryFolders' => $libraryFolders,
            'academyFolders' => $academyFolders,
            'teacherFolders' => $teacherFolders,
            'activeFolder' => $activeFolder,
            'uncategorizedCount' => $uncategorizedCount,
            'familyThemes' => FamilyLibraryThemes::all(),
            'hasLibraryEntitlement' => $hasLibraryEntitlement,
            'linkedTeacherCount' => $linkedTeacherCount,
            'academyFolderCount' => $academyFolderCount,
            'teacherFolderCount' => $teacherFolderCount,
            'packagesUrl' => Route::has('public.service-packages.index')
                ? route('public.service-packages.index')
                : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard')),
        ]);
    }

    /**
     * @return list<string>
     */
    private function materialTypeExtensions(string $type): array
    {
        return match ($type) {
            'pdf' => ['pdf'],
            'doc' => ['doc', 'docx', 'docm'],
            'ppt' => ['ppt', 'pptx'],
            'sheet' => ['xls', 'xlsx'],
            'zip' => ['zip', 'rar'],
            'html' => ['html', 'htm'],
            'image' => ['png', 'jpg', 'jpeg', 'webp', 'gif'],
            'audio' => ['mp3', 'wav', 'm4a'],
            'video' => ['mp4', 'mov', 'webm'],
            default => [],
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<string, int>
     */
    private function materialTypeCounts($rows): array
    {
        $counts = [
            'all' => $rows->count(),
            'pdf' => 0,
            'doc' => 0,
            'ppt' => 0,
            'sheet' => 0,
            'zip' => 0,
            'html' => 0,
            'image' => 0,
            'audio' => 0,
            'video' => 0,
            'other' => 0,
        ];

        foreach ($rows as $row) {
            $name = (string) ($row->file_name ?: $row->file_path ?: '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $bucket = match (true) {
                $ext === 'pdf' => 'pdf',
                in_array($ext, ['doc', 'docx', 'docm'], true) => 'doc',
                in_array($ext, ['ppt', 'pptx'], true) => 'ppt',
                in_array($ext, ['xls', 'xlsx'], true) => 'sheet',
                in_array($ext, ['zip', 'rar'], true) => 'zip',
                in_array($ext, ['html', 'htm'], true) => 'html',
                in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true) => 'image',
                in_array($ext, ['mp3', 'wav', 'm4a'], true) => 'audio',
                in_array($ext, ['mp4', 'mov', 'webm'], true) => 'video',
                default => 'other',
            };
            $counts[$bucket]++;
        }

        return $counts;
    }

    public function downloadMaterial(Request $request, LectureMaterial $material): StreamedResponse
    {
        $this->assertStudentCanAccessMaterial($request->user(), $material);

        return LectureMaterialStorage::download($material);
    }

    /**
     * تشغيل HTML / لعبة تعليمية داخل المنصة (بدون الخروج ليوتيوب).
     */
    public function experienceMaterial(Request $request, LectureMaterial $material): View
    {
        $this->assertStudentCanAccessMaterial($request->user(), $material);

        $mode = $material->experience_mode
            ?: FamilyLibraryThemes::detectExperienceMode($material->file_name, $material->content_theme);
        abort_unless(
            FamilyLibraryThemes::isPlayableInPlatform($material->file_name, $mode),
            404,
            'هذا الملف لا يُشغَّل داخل المنصة.'
        );

        return view('student.library.material-experience', [
            'material' => $material,
            'frameUrl' => route('student.library.materials.experience.raw', $material),
            'isGame' => $mode === FamilyLibraryThemes::MODE_PLAY
                || ($material->content_theme === FamilyLibraryThemes::GAMES),
        ]);
    }

    public function experienceMaterialRaw(Request $request, LectureMaterial $material): Response
    {
        $this->assertStudentCanAccessMaterial($request->user(), $material);

        $mode = $material->experience_mode
            ?: FamilyLibraryThemes::detectExperienceMode($material->file_name, $material->content_theme);
        abort_unless(
            FamilyLibraryThemes::isPlayableInPlatform($material->file_name, $mode),
            404
        );

        $html = LectureMaterialStorage::getContents($material);
        abort_unless(is_string($html) && $html !== '', 404, 'تعذر تحميل المحتوى.');

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https:; img-src * data: blob:; media-src * data: blob: https:; style-src * 'unsafe-inline'; font-src * data:;",
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function assertStudentCanAccessMaterial($user, LectureMaterial $material): void
    {
        abort_unless((bool) $material->is_visible_to_student || $material->is_visible_to_student === null, 404);

        $material->loadMissing(['lecture:id,course_id', 'folder']);

        if ($material->library_folder_id && $material->folder) {
            abort_unless(
                LibraryFolderAccessService::canAccessFolder($user, $material->folder),
                403,
                'يلزم اشتراك باقة المكتبات لهذه السنة لتحميل الملف.'
            );

            return;
        }

        $courseId = $material->lecture?->course_id;
        abort_unless($courseId, 404);

        $enrolled = false;
        if (Schema::hasTable('student_course_enrollments')) {
            $enrolled = DB::table('student_course_enrollments')
                ->where('user_id', $user->id)
                ->where('advanced_course_id', $courseId)
                ->when(Schema::hasColumn('student_course_enrollments', 'status'), fn ($query) => $query->where('status', 'active'))
                ->exists();
        }

        abort_unless($enrolled, 403, 'ليس لديك صلاحية تحميل هذا الملف.');
    }

    public function videos(Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyLegacyLibraries()) {
            return $deny;
        }

        $q = trim((string) $request->query('q', ''));
        $folderId = $request->query('folder');
        $theme = strtolower(trim((string) $request->query('theme', '')));
        if ($theme !== '' && ! in_array($theme, FamilyLibraryThemes::keys(), true)) {
            $theme = '';
        }
        $activeFolder = null;
        $user = $request->user();

        $folders = collect();
        $uncategorizedCount = 0;
        $videos = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);
        $academyCount = 0;
        $teacherCount = 0;

        if (Schema::hasTable('library_folders') && Schema::hasTable('library_videos')) {
            $teacherIds = \App\Services\StudentTeacherLinkService::instructorIdsForStudent($user);

            $folders = LibraryFolderAccessService::foldersVisibleTo($user, LibraryFolder::KIND_VIDEOS)
                ->with(['academicYear:id,name', 'instructor:id,name'])
                ->withCount(['libraryVideos' => function ($query) use ($teacherIds) {
                    $query->published()->where(function ($inner) use ($teacherIds) {
                        $inner->where(function ($g) {
                            $g->where('audience', \App\Models\LibraryVideo::AUDIENCE_GENERAL)
                                ->orWhereNull('audience');
                        });
                        if ($teacherIds !== []) {
                            $inner->orWhere(function ($t) use ($teacherIds) {
                                $t->where('audience', \App\Models\LibraryVideo::AUDIENCE_TEACHER_STUDENTS)
                                    ->whereIn('instructor_id', $teacherIds);
                            });
                        }
                    });
                }])
                ->get();

            $allowedFolderIds = $folders->pluck('id');
            $base = LibraryFolderAccessService::videosVisibleTo($user);

            $uncategorizedCount = (clone $base)->whereNull('library_folder_id')->count();
            $academyCount = (clone $base)->where(function ($query) {
                $query->where('audience', \App\Models\LibraryVideo::AUDIENCE_GENERAL)
                    ->orWhereNull('audience');
            })->count();
            $teacherCount = (clone $base)->where('audience', \App\Models\LibraryVideo::AUDIENCE_TEACHER_STUDENTS)->count();

            if ($folderId === 'none') {
                $activeFolder = (object) [
                    'id' => 'none',
                    'slug' => 'none',
                    'is_uncategorized' => true,
                ];
            } elseif (filled($folderId)) {
                $activeFolder = LibraryFolderAccessService::resolveFolderFromParam($folderId);
                abort_unless(
                    $activeFolder && $allowedFolderIds->contains((int) $activeFolder->id),
                    403,
                    'هذا المجلد غير متاح لك.'
                );
                abort_unless(
                    LibraryFolderAccessService::canAccessFolder($user, $activeFolder),
                    403,
                    'هذا المجلد غير متاح لك.'
                );
            }

            $videos = (clone $base)
                ->with(['folder', 'instructor:id,name'])
                ->when($activeFolder && ! ($activeFolder->is_uncategorized ?? false), function ($query) use ($activeFolder) {
                    $query->where('library_folder_id', $activeFolder->id);
                })
                ->when($activeFolder && ($activeFolder->is_uncategorized ?? false), function ($query) {
                    $query->whereNull('library_folder_id');
                })
                ->when($theme !== '' && Schema::hasColumn('library_videos', 'content_theme'), function ($query) use ($theme) {
                    $query->where('content_theme', $theme);
                })
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('title', 'like', '%'.$q.'%')
                            ->orWhere('description', 'like', '%'.$q.'%');
                        if (Schema::hasColumn('library_videos', 'series_title')) {
                            $inner->orWhere('series_title', 'like', '%'.$q.'%');
                        }
                    });
                })
                ->ordered()
                ->paginate(24)
                ->withQueryString();
        }

        $hasLibraryEntitlement = LibraryFolderAccessService::hasAnyLibraryEntitlement($user);
        $linkedTeacherCount = count(\App\Services\StudentTeacherLinkService::instructorIdsForStudent($user));

        return view('student.library.videos', [
            'videos' => $videos,
            'lectureRecordings' => collect(),
            'folders' => $folders,
            'activeFolder' => $activeFolder,
            'uncategorizedCount' => $uncategorizedCount,
            'searchQuery' => $q,
            'themeFilter' => $theme,
            'sourceFilter' => 'library',
            'academyCount' => $academyCount,
            'teacherCount' => $teacherCount,
            'familyThemes' => FamilyLibraryThemes::all(),
            'hasLibraryEntitlement' => $hasLibraryEntitlement,
            'linkedTeacherCount' => $linkedTeacherCount,
            'packagesUrl' => Route::has('public.service-packages.index')
                ? route('public.service-packages.index')
                : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard')),
        ]);
    }

    /**
     * مشاهدة فيديو المكتبة (عام من الإدارة أو من معلم الطالب).
     */
    public function watchLibraryVideo(Request $request, \App\Models\LibraryVideo $libraryVideo): View
    {
        $user = $request->user();
        abort_unless(
            LibraryFolderAccessService::canAccessVideo($user, $libraryVideo),
            403,
            'هذا الفيديو غير متاح لك.'
        );

        $url = $libraryVideo->getUrl();
        abort_unless($url, 404, 'رابط الفيديو غير متوفر حالياً');

        $embedUrl = VideoHelper::getEmbedUrl($url);
        $directUrl = $embedUrl ? null : (VideoHelper::getDirectVideoUrl($url) ?: $url);
        $source = VideoHelper::getVideoSource($url);
        $thumbnail = VideoHelper::getThumbnail($url);

        return view('student.library.video-show', [
            'libraryVideo' => $libraryVideo,
            'url' => $url,
            'embedUrl' => $embedUrl,
            'directUrl' => $directUrl,
            'source' => $source,
            'thumbnail' => $thumbnail,
        ]);
    }

    /**
     * مشاهدة تسجيل محاضرة (مسار كورس — ليس مكتبة عامة).
     */
    public function watchLectureRecording(Request $request, Lecture $lecture): View
    {
        $user = $request->user();
        $courseId = (int) $lecture->course_id;
        abort_unless($courseId > 0, 404);

        $enrolled = false;
        if (Schema::hasTable('student_course_enrollments')) {
            $enrolled = DB::table('student_course_enrollments')
                ->where('user_id', $user->id)
                ->where('advanced_course_id', $courseId)
                ->when(Schema::hasColumn('student_course_enrollments', 'status'), fn ($query) => $query->where('status', 'active'))
                ->exists();
        }
        abort_unless($enrolled, 403, 'يلزم التسجيل في الكورس لمشاهدة هذا التسجيل.');

        $lecture->load(['course:id,title', 'instructor:id,name']);

        $url = $lecture->recording_url ? trim((string) $lecture->recording_url) : null;
        $fileUrl = null;
        if (! $url && Schema::hasColumn('lectures', 'recording_file_path') && $lecture->recording_file_path) {
            $fileUrl = route('my-courses.lectures.recording-stream', [$courseId, $lecture->id]);
            $url = $fileUrl;
        }

        abort_unless($url, 404, 'لا يوجد تسجيل لهذه المحاضرة.');

        $embedUrl = $fileUrl ? null : VideoHelper::getEmbedUrl($url);
        $directUrl = $fileUrl ?: ($embedUrl ? null : (VideoHelper::getDirectVideoUrl($url) ?: $url));
        $source = $fileUrl ? 'direct' : VideoHelper::getVideoSource($url);
        $thumbnail = $fileUrl ? null : VideoHelper::getThumbnail($url);

        return view('student.library.lecture-recording-show', [
            'lecture' => $lecture,
            'url' => $url,
            'embedUrl' => $embedUrl,
            'directUrl' => $directUrl,
            'source' => $source,
            'thumbnail' => $thumbnail,
        ]);
    }

    public function lectures(Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyLegacyLibraries()) {
            return $deny;
        }

        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'private', 'classes'], true)) {
            $filter = 'all';
        }

        $private = collect();
        $classes = collect();

        if (Schema::hasTable('one_to_one_sessions')) {
            $private = OneToOneSession::query()
                ->with(['course:id,title', 'instructor:id,name,profile_image', 'classroomMeeting'])
                ->where('student_id', $user->id)
                ->whereIn('status', [OneToOneSession::STATUS_SCHEDULED, OneToOneSession::STATUS_COMPLETED])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->whereHas('course', fn ($cq) => $cq->where('title', 'like', '%'.$q.'%'))
                            ->orWhereHas('instructor', fn ($iq) => $iq->where('name', 'like', '%'.$q.'%'));
                    });
                })
                ->orderByDesc('scheduled_at')
                ->limit(40)
                ->get();
        }

        if (Schema::hasTable('tutoring_class_sessions') && Schema::hasTable('tutoring_cohort_enrollments')) {
            $cohortIds = TutoringCohortEnrollment::query()
                ->where('user_id', $user->id)
                ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                ->pluck('tutoring_group_cohort_id');

            $classes = TutoringClassSession::query()
                ->with(['cohort:id,title', 'tutoringGroup:id,title', 'classroomMeeting'])
                ->whereIn('tutoring_group_cohort_id', $cohortIds)
                ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('title', 'like', '%'.$q.'%')
                            ->orWhereHas('cohort', fn ($cq) => $cq->where('title', 'like', '%'.$q.'%'))
                            ->orWhereHas('tutoringGroup', fn ($gq) => $gq->where('title', 'like', '%'.$q.'%'));
                    });
                })
                ->orderByDesc('starts_at')
                ->limit(40)
                ->get();
        }

        $nextJoinable = null;
        foreach ($classes as $session) {
            if (method_exists($session, 'isJoinable') && $session->isJoinable()) {
                $nextJoinable = (object) [
                    'kind' => 'class',
                    'title' => $session->displayTitle(),
                    'meta' => $session->cohort?->title,
                    'at' => $session->starts_at,
                    'join_url' => route('student.schedule.join', ['type' => 'class', 'id' => $session->id]),
                ];
                break;
            }
        }
        if (! $nextJoinable) {
            foreach ($private as $session) {
                $canJoin = $session->status === OneToOneSession::STATUS_SCHEDULED
                    && $session->scheduled_at
                    && $session->scheduled_at->lte(now()->addMinutes(30))
                    && $session->scheduled_at->gte(now()->subMinutes(50));
                if ($canJoin) {
                    $nextJoinable = (object) [
                        'kind' => 'private',
                        'title' => $session->course?->title ?: (__('student_timeline.private_lesson')),
                        'meta' => $session->instructor?->name,
                        'at' => $session->scheduled_at,
                        'join_url' => route('student.schedule.join', ['type' => 'private', 'id' => $session->id]),
                    ];
                    break;
                }
            }
        }

        return view('student.library.lectures', [
            'private' => $private,
            'classes' => $classes,
            'searchQuery' => $q,
            'filter' => $filter,
            'nextJoinable' => $nextJoinable,
        ]);
    }
}
