<?php

namespace App\Services;

use App\Models\InstructorProfile;
use App\Models\User;
use App\Support\InstructorProfilePublication;
use App\Support\PublicDemoContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class PublicInstructorDirectory
{
    /**
     * @return list<string>
     */
    public static function userSelect(): array
    {
        return ['id', 'uuid', 'name', 'role', 'is_active', 'bio', 'profile_image'];
    }

    public static function approvedActiveQuery(): Builder
    {
        return InstructorProfile::query()
            ->approved()
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['instructor', 'teacher'])
                    ->where('is_active', true);
            });
    }

    /**
     * معتمدون ظاهرون للعامة فقط بعد اجتياز بوابة النشر (مطابقة + بلا تجريبي).
     *
     * @return \Illuminate\Support\Collection<int, InstructorProfile>
     */
    public static function publiclyListableProfiles(?callable $tap = null)
    {
        $query = self::approvedActiveQuery()->with(['user:'.implode(',', self::userSelect())]);
        if ($tap) {
            $tap($query);
        }

        return $query->get()->filter(fn (InstructorProfile $p) => self::isPublishableListing($p))->values();
    }

    public static function isPublishableListing(InstructorProfile $profile): bool
    {
        $profile->loadMissing('user.teachingLearningPaths');

        return InstructorProfilePublication::isPublishable($profile);
    }

    public static function isPubliclyListed(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if (! in_array($user->role, ['instructor', 'teacher'], true) && ! $user->isInstructor()) {
            return false;
        }

        $profile = InstructorProfile::query()
            ->where('user_id', $user->id)
            ->approved()
            ->with(['user:'.implode(',', self::userSelect()), 'user.teachingLearningPaths'])
            ->first();

        if (! $profile || ! $profile->user) {
            return false;
        }

        return self::isPublishableListing($profile);
    }

    public static function profileLooksLikeDemo(InstructorProfile $profile): bool
    {
        $user = $profile->user;
        $skills = $profile->skills;
        $skillText = is_array($skills) ? implode(' ', $skills) : (string) $skills;

        return PublicDemoContent::recordLooksLikeDemo([
            $user?->name,
            $profile->headline,
            $profile->bio,
            $skillText,
            $profile->experience,
        ]);
    }

    /**
     * @return User|RedirectResponse
     */
    public static function resolvePublicShow(string $key): User|RedirectResponse
    {
        $key = trim($key);
        if ($key === '') {
            return redirect()->route('public.instructors.index', [], 302);
        }

        $user = User::query()->where('uuid', $key)->first();

        if (! $user && ctype_digit($key)) {
            $byId = User::query()->find((int) $key);
            if ($byId && self::isPubliclyListed($byId)) {
                return redirect()->route('public.instructors.show', $byId, 301);
            }
            if ($byId) {
                abort(410);
            }

            return redirect()->route('public.instructors.index', [], 302);
        }

        if (! $user) {
            return redirect()->route('public.instructors.index', [], 302);
        }

        if (! self::isPubliclyListed($user)) {
            throw new HttpException(410, 'Gone');
        }

        return $user;
    }
}
