<?php

namespace App\Services;

use App\Models\Setting;

class HowItWorksMediaSettings
{
    public const KEY_STUDENT = 'how_it_works_video_student';

    public const KEY_TEACHER = 'how_it_works_video_teacher';

    public static function studentUrl(): ?string
    {
        $v = trim((string) (Setting::getValue(self::KEY_STUDENT) ?? ''));

        return $v !== '' ? $v : null;
    }

    public static function teacherUrl(): ?string
    {
        $v = trim((string) (Setting::getValue(self::KEY_TEACHER) ?? ''));

        return $v !== '' ? $v : null;
    }

    public static function setStudentUrl(?string $url): void
    {
        Setting::setValue(self::KEY_STUDENT, $url);
    }

    public static function setTeacherUrl(?string $url): void
    {
        Setting::setValue(self::KEY_TEACHER, $url);
    }
}
