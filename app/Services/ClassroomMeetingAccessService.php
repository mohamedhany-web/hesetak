<?php

namespace App\Services;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\TutoringClassSession;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * صلاحيات دخول غرف Classroom / Live بين الطالب والمعلم بدون رابط ضيف قابل للمشاركة.
 */
class ClassroomMeetingAccessService
{
    public static function isPlatformBound(ClassroomMeeting $meeting): bool
    {
        if ($meeting->one_to_one_session_id
            || $meeting->tutoring_group_booking_id
            || $meeting->consultation_request_id) {
            return true;
        }

        if (filled(data_get($meeting->settings, 'tutoring_class_session_id'))) {
            return true;
        }

        // بيانات قديمة: الربط من جهة الحصة فقط
        if (Schema::hasTable('one_to_one_sessions')
            && OneToOneSession::query()->where('classroom_meeting_id', $meeting->id)->exists()) {
            return true;
        }
        if (Schema::hasTable('tutoring_group_bookings')
            && TutoringGroupBooking::query()->where('classroom_meeting_id', $meeting->id)->exists()) {
            return true;
        }
        if (Schema::hasTable('consultation_requests')
            && \App\Models\ConsultationRequest::query()->where('classroom_meeting_id', $meeting->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * رابط الضيوف العام مسموح فقط لاجتماعات مفتوحة غير مربوطة بحصة خاصة، وبعد تفعيل صريح.
     */
    public static function allowsGuestJoin(ClassroomMeeting $meeting): bool
    {
        if (self::isPlatformBound($meeting)) {
            return false;
        }

        return (bool) data_get($meeting->settings, 'allow_guest_join', false);
    }

    public static function userCanEnter(ClassroomMeeting $meeting, User $user): bool
    {
        if ((int) $meeting->user_id === (int) $user->id) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (Schema::hasTable('one_to_one_sessions')) {
            $session = null;
            if ($meeting->one_to_one_session_id) {
                $session = $meeting->relationLoaded('oneToOneSession')
                    ? $meeting->oneToOneSession
                    : OneToOneSession::query()->find($meeting->one_to_one_session_id);
            }
            if (! $session) {
                $session = OneToOneSession::query()
                    ->where('classroom_meeting_id', $meeting->id)
                    ->first();
            }

            if ($session && (
                (int) $session->student_id === (int) $user->id
                || (int) $session->instructor_id === (int) $user->id
            )) {
                if ((int) $session->student_id === (int) $user->id
                    && $user->isStudent()
                    && ! OneToOneSessionUnlockService::canStudentJoin($session, $user)) {
                    return false;
                }

                return true;
            }
        }

        if ($meeting->tutoring_group_booking_id && Schema::hasTable('tutoring_group_bookings')) {
            $booking = $meeting->relationLoaded('tutoringGroupBooking')
                ? $meeting->tutoringGroupBooking
                : TutoringGroupBooking::query()->find($meeting->tutoring_group_booking_id);

            if (! $booking) {
                $booking = TutoringGroupBooking::query()
                    ->where('classroom_meeting_id', $meeting->id)
                    ->first();
            }

            if ($booking && (
                (int) $booking->user_id === (int) $user->id
                || (int) $booking->instructor_id === (int) $user->id
            )) {
                return true;
            }
        }

        if ($meeting->consultation_request_id && Schema::hasTable('consultation_requests')) {
            $consultation = $meeting->consultationRequest;
            if (! $consultation) {
                $consultation = \App\Models\ConsultationRequest::query()
                    ->where('classroom_meeting_id', $meeting->id)
                    ->first();
            }
            if ($consultation && (
                (int) ($consultation->student_id ?? 0) === (int) $user->id
                || (int) ($consultation->instructor_id ?? 0) === (int) $user->id
            )) {
                return true;
            }
        }

        $classSessionId = (int) data_get($meeting->settings, 'tutoring_class_session_id', 0);
        if ($classSessionId > 0 && Schema::hasTable('tutoring_class_sessions')) {
            $classSession = TutoringClassSession::query()
                ->with(['cohort', 'tutoringGroup'])
                ->find($classSessionId);

            if ($classSession) {
                $instructorId = (int) ($classSession->tutoringGroup?->instructor_id ?? 0);
                if ($instructorId === (int) $user->id) {
                    return true;
                }
                if ($classSession->cohort && TutoringClassService::userCanAccessCohort($user, $classSession->cohort)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function userIsHost(ClassroomMeeting $meeting, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // الطالب لا يُعامل كمضيف حتى لو كان user_id على الاجتماع (بيانات قديمة).
        if ($user->isStudent() && ! $user->isInstructor()) {
            return false;
        }

        if ((int) $meeting->user_id === (int) $user->id) {
            return $user->isInstructor();
        }

        if ($meeting->one_to_one_session_id) {
            $session = $meeting->relationLoaded('oneToOneSession')
                ? $meeting->oneToOneSession
                : OneToOneSession::query()->find($meeting->one_to_one_session_id);

            return $session && (int) $session->instructor_id === (int) $user->id;
        }

        if ($meeting->tutoring_group_booking_id) {
            $booking = $meeting->relationLoaded('tutoringGroupBooking')
                ? $meeting->tutoringGroupBooking
                : TutoringGroupBooking::query()->find($meeting->tutoring_group_booking_id);

            return $booking && (int) $booking->instructor_id === (int) $user->id;
        }

        $classSessionId = (int) data_get($meeting->settings, 'tutoring_class_session_id', 0);
        if ($classSessionId > 0) {
            $classSession = TutoringClassSession::query()->with('tutoringGroup')->find($classSessionId);

            return $classSession && (int) ($classSession->tutoringGroup?->instructor_id ?? 0) === (int) $user->id;
        }

        if ($meeting->consultation_request_id) {
            $consultation = $meeting->consultationRequest;

            return $consultation && (int) ($consultation->instructor_id ?? 0) === (int) $user->id;
        }

        return false;
    }

    /**
     * رابط دخول داخل المنصة فقط (يتطلب تسجيل دخول + صلاحية).
     */
    public static function platformEnterUrl(ClassroomMeeting $meeting): string
    {
        if (Route::has('classroom.secure-enter')) {
            return route('classroom.secure-enter', $meeting);
        }

        return url('/classroom/enter/'.$meeting->id);
    }

    public static function roomUrlForUser(ClassroomMeeting $meeting, User $user): string
    {
        // الأدمن يدخل غرفة الطالب/المراقبة — مسار المعلم محمي بـ role:instructor فقط.
        $isInstructorSide = ($user->isInstructor() || self::userIsHost($meeting, $user))
            && ! $user->isAdmin();

        if ($isInstructorSide && Route::has('instructor.classroom.room')) {
            return route('instructor.classroom.room', $meeting);
        }

        // احتياط: بعض البيئات قد تُسجّل الاسم بدون بادئة instructor.
        if ($isInstructorSide && Route::has('classroom.room') && ! Route::has('student.classroom.room')) {
            return route('classroom.room', $meeting);
        }

        if (Route::has('student.classroom.room')) {
            return route('student.classroom.room', $meeting);
        }

        if (Route::has('classroom.room')) {
            return route('classroom.room', $meeting);
        }

        return self::platformEnterUrl($meeting);
    }
}
