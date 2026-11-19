<?php

namespace App\Services;

use App\Models\LibraryFolder;
use App\Models\LibraryVideo;
use App\Models\StudentServiceEntitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * بوابة ظهور فولدرات المكتبة (ماتريال/فيديو):
 * - عام من الإدارة: باقة المكتبات / مجاني
 * - فولدر معلم: لطلاب هذا المعلم فقط (وليس للعامة)
 */
class LibraryFolderAccessService
{
    public static function canAccessFolder(?User $user, LibraryFolder $folder): bool
    {
        if (! $user || ! $folder->is_active) {
            return false;
        }

        // فولدر معلم → طلاب هذا المعلم فقط
        if ($folder->instructor_id) {
            return StudentTeacherLinkService::studentStudiesWith($user, (int) $folder->instructor_id);
        }

        if (! Schema::hasColumn('library_folders', 'requires_library_entitlement')) {
            return true;
        }

        if (! $folder->requires_library_entitlement) {
            return true;
        }

        return self::hasLibraryEntitlementForYear($user, $folder->academic_year_id ? (int) $folder->academic_year_id : null);
    }

    public static function canAccessVideo(?User $user, LibraryVideo $video): bool
    {
        if (! $user || ! $video->is_published) {
            return false;
        }

        if ($video->isTeacherPrivate()) {
            if (! $video->instructor_id) {
                return false;
            }

            return StudentTeacherLinkService::studentStudiesWith($user, (int) $video->instructor_id);
        }

        if ($video->folder) {
            return self::canAccessFolder($user, $video->folder);
        }

        // فيديو عام منشور بدون مجلد: متاح لكل الطلاب (التقييد بالباقة يتم عبر مجلد «يتطلب باقة»)
        return true;
    }

    /**
     * هل يوجد فيديو عام منشور متاح بلا باقة (بدون مجلد أو داخل مجلد مجاني)؟ — لإظهار تبويب الفيديوهات للطلاب.
     */
    public static function hasPublicVideos(): bool
    {
        if (! Schema::hasTable('library_videos')) {
            return false;
        }

        return Cache::remember('library.has_public_videos', 60, function (): bool {
            return LibraryVideo::query()
                ->published()
                ->where(function (Builder $audience) {
                    $audience->where('audience', LibraryVideo::AUDIENCE_GENERAL)->orWhereNull('audience');
                })
                ->where(function (Builder $folder) {
                    $folder->whereNull('library_folder_id')
                        ->orWhereIn('library_folder_id', LibraryFolder::query()
                            ->active()
                            ->whereNull('instructor_id')
                            ->where('requires_library_entitlement', false)
                            ->select('id'));
                })
                ->exists();
        });
    }

    /**
     * هل لدى الطالب entitlement مكتبات نشط (أي سنة)؟
     */
    public static function hasAnyLibraryEntitlement(User $user): bool
    {
        $years = self::accessibleYearIds($user);

        return $years !== [];
    }

    public static function hasLibraryEntitlementForYear(User $user, ?int $academicYearId): bool
    {
        if (! Schema::hasTable('student_service_entitlements')) {
            return false;
        }

        StudentEntitlementService::expireStaleForUser((int) $user->id);

        $ents = StudentServiceEntitlement::query()
            ->forUser((int) $user->id)
            ->active()
            ->where('includes_libraries', true)
            ->get();

        if ($ents->isEmpty()) {
            return false;
        }

        foreach ($ents as $e) {
            if (! $e->academic_year_id) {
                return true;
            }
            if ($academicYearId && (int) $e->academic_year_id === (int) $academicYearId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int|string>
     */
    public static function accessibleYearIds(User $user): array
    {
        if (! Schema::hasTable('student_service_entitlements')) {
            return [];
        }

        StudentEntitlementService::expireStaleForUser((int) $user->id);

        $ents = StudentServiceEntitlement::query()
            ->forUser((int) $user->id)
            ->active()
            ->where('includes_libraries', true)
            ->get(['academic_year_id']);

        if ($ents->isEmpty()) {
            return [];
        }

        if ($ents->contains(fn ($e) => ! $e->academic_year_id)) {
            return ['*'];
        }

        return $ents->pluck('academic_year_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * فولدرات ظاهرة للطالب: عام (إدارة) حسب الباقة + فولدرات معلميه فقط.
     */
    public static function foldersVisibleTo(User $user, string $kind = 'materials'): Builder
    {
        $q = LibraryFolder::query()
            ->active()
            ->ordered()
            ->ofKind($kind);

        $teacherIds = StudentTeacherLinkService::instructorIdsForStudent($user);
        $years = self::accessibleYearIds($user);

        return $q->where(function (Builder $outer) use ($years, $teacherIds) {
            // 1) فولدرات الإدارة العامة (بدون معلم)
            $outer->where(function (Builder $general) use ($years) {
                $general->whereNull('instructor_id');

                if (! Schema::hasColumn('library_folders', 'requires_library_entitlement')) {
                    return;
                }

                $general->where(function (Builder $access) use ($years) {
                    $access->where('requires_library_entitlement', false);

                    if (in_array('*', $years, true)) {
                        $access->orWhere('requires_library_entitlement', true);
                    } elseif ($years !== []) {
                        $access->orWhere(function (Builder $gated) use ($years) {
                            $gated->where('requires_library_entitlement', true)
                                ->where(function (Builder $yearQ) use ($years) {
                                    $yearQ->whereIn('academic_year_id', $years)
                                        ->orWhereNull('academic_year_id');
                                });
                        });
                    }
                });
            });

            // 2) فولدرات المعلمون المرتبطون بالطالب فقط
            if ($teacherIds !== []) {
                $outer->orWhereIn('instructor_id', $teacherIds);
            }
        });
    }

    /**
     * استعلام فيديوهات ظاهرة للطالب: عام من الإدارة (باقة) + من معلميه.
     */
    public static function videosVisibleTo(User $user): Builder
    {
        $teacherIds = StudentTeacherLinkService::instructorIdsForStudent($user);
        $allowedFolderIds = self::foldersVisibleTo($user, LibraryFolder::KIND_VIDEOS)->pluck('id');
        $hasLibraryEntitlement = self::hasAnyLibraryEntitlement($user);

        return LibraryVideo::query()
            ->published()
            ->where(function (Builder $q) use ($teacherIds, $allowedFolderIds, $hasLibraryEntitlement) {
                // عام من الإدارة داخل مجلدات مسموحة
                $q->where(function (Builder $general) use ($allowedFolderIds, $hasLibraryEntitlement) {
                    $general->where(function ($a) {
                        $a->where('audience', LibraryVideo::AUDIENCE_GENERAL)
                            ->orWhereNull('audience');
                    })->where(function ($folderQ) use ($allowedFolderIds, $hasLibraryEntitlement) {
                        if ($allowedFolderIds->isNotEmpty()) {
                            $folderQ->whereIn('library_folder_id', $allowedFolderIds);
                        } else {
                            $folderQ->whereRaw('1 = 0');
                        }

                        // بدون مجلد: متاح لكل الطلاب
                        $folderQ->orWhereNull('library_folder_id');
                    });
                });

                // من معلمي الطالب فقط
                if ($teacherIds !== []) {
                    $q->orWhere(function (Builder $teacher) use ($teacherIds) {
                        $teacher->where('audience', LibraryVideo::AUDIENCE_TEACHER_STUDENTS)
                            ->whereIn('instructor_id', $teacherIds);
                    });
                }
            });
    }

    public static function resolveFolderFromParam(string|int|null $folderId): ?LibraryFolder
    {
        if ($folderId === null || $folderId === '' || $folderId === 'none') {
            return null;
        }

        return LibraryFolder::query()
            ->active()
            ->where(function (Builder $query) use ($folderId) {
                if (ctype_digit((string) $folderId)) {
                    $query->where('id', (int) $folderId);
                } else {
                    $query->where('slug', (string) $folderId);
                }
            })
            ->first();
    }
}
