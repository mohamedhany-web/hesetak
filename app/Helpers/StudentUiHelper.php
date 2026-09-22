<?php

if (! function_exists('student_ui')) {
    /**
     * هل يظهر قسم في واجهة الطالب؟
     * يُفعَّل تلقائياً أقسام الكورسات المسجّلة عند وجود تسجيل نشط.
     */
    function student_ui(string $key, bool $default = false): bool
    {
        $configured = (bool) config('student_ui.'.$key, $default);

        $courseKeys = [
            'show_courses',
            'show_course_progress',
            'show_assignments',
            'show_exams',
            'show_certificates',
        ];

        if (in_array($key, $courseKeys, true) && auth()->check()) {
            $user = auth()->user();
            if ($user && method_exists($user, 'hasActiveRecordedCourses') && $user->hasActiveRecordedCourses()) {
                return true;
            }
        }

        return $configured;
    }
}

if (! function_exists('instructor_ui')) {
    /**
     * هل يظهر قسم في واجهة المعلم؟
     * يُفعَّل تلقائياً أدوات الكورسات عند إسناد كورس مسجّل للمعلم.
     */
    function instructor_ui(string $key, bool $default = false): bool
    {
        $configured = (bool) config('instructor_ui.'.$key, $default);

        if ($key === 'show_courses' && auth()->check()) {
            $user = auth()->user();
            if ($user && method_exists($user, 'hasTeachingCourses') && $user->hasTeachingCourses()) {
                return true;
            }
        }

        return $configured;
    }
}
