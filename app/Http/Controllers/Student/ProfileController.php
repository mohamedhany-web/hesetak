<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\UserProfileImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * عرض البروفايل
     */
    public function index()
    {
        $user = auth()->user();
        $profileImageUrl = $user->profile_image_url;
        $academicYears = \App\Support\StudentLearningProfile::publicYears();
        $gradesByYear = \App\Support\StudentLearningProfile::gradesByYearMap($academicYears);
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();
        $children = $user->role === 'parent'
            ? $user->children()->with('academicYear:id,name')->orderBy('name')->get()
            : collect();

        return view('student.profile.index', compact('user', 'profileImageUrl', 'academicYears', 'gradesByYear', 'curriculumTypes', 'children'));
    }

    /**
     * تحديث البروفايل
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'timezone' => 'required|string|max:64',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'academic_grade_id' => array_values(array_filter([
                'nullable',
                'integer',
                \Illuminate\Support\Facades\Schema::hasTable('academic_grades') ? 'exists:academic_grades,id' : null,
            ])),
            'preferred_curriculum_type' => 'nullable|string|max:40',
            'current_password' => 'nullable|string',
            'password' => ['nullable', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'profile_image' => 'nullable|image|max:'.config('upload_limits.max_upload_kb'),
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'timezone.required' => 'المنطقة الزمنية مطلوبة',
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'profile_image.image' => 'الملف الذي تم رفعه يجب أن يكون صورة',
            'profile_image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        $timezone = \App\Support\AppTimezone::normalize($request->input('timezone'));
        if (! $timezone) {
            return back()->withErrors(['timezone' => 'منطقة زمنية غير صالحة.'])->withInput();
        }

        // التحقق من كلمة المرور الحالية عند تغيير كلمة المرور
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
            }
        }

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'timezone' => $timezone,
        ];

        if ($request->filled('academic_year_id')) {
            $data['academic_year_id'] = (int) $request->input('academic_year_id');
        } elseif ($request->has('academic_year_id')) {
            $data['academic_year_id'] = null;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_grade_id')) {
            $yearId = array_key_exists('academic_year_id', $data)
                ? $data['academic_year_id']
                : $user->academic_year_id;
            $gradeId = $request->filled('academic_grade_id') ? (int) $request->input('academic_grade_id') : null;

            if (! $yearId) {
                $data['academic_grade_id'] = null;
            } else {
                $hasGrades = \App\Models\AcademicGrade::query()
                    ->where('academic_year_id', $yearId)
                    ->where('is_active', true)
                    ->exists();
                if ($hasGrades && ! $gradeId) {
                    return back()->withErrors(['academic_grade_id' => 'اختر الصف الدراسي لهذه المرحلة.'])->withInput();
                }
                if ($gradeId) {
                    $gradeOk = \App\Models\AcademicGrade::query()
                        ->where('id', $gradeId)
                        ->where('academic_year_id', $yearId)
                        ->where('is_active', true)
                        ->exists();
                    if (! $gradeOk) {
                        return back()->withErrors(['academic_grade_id' => 'الصف لا يتبع المرحلة المختارة.'])->withInput();
                    }
                }
                $data['academic_grade_id'] = $gradeId;
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'preferred_curriculum_type')) {
            $curriculum = strtolower(trim((string) $request->input('preferred_curriculum_type', '')));
            if ($curriculum === '') {
                $data['preferred_curriculum_type'] = null;
            } elseif (in_array($curriculum, \App\Support\HesetakMatchCatalog::allowedCurriculumTypeKeys(), true)) {
                $data['preferred_curriculum_type'] = $curriculum;
            } else {
                return back()->withErrors(['preferred_curriculum_type' => 'نوع المنهج غير صالح.'])->withInput();
            }
        }

        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_image')) {
            UserProfileImageStorage::delete($user->profile_image);
            $data['profile_image'] = UserProfileImageStorage::store($request->file('profile_image'));
        }

        $user->update($data);
        $user->refresh();

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تحديث الملف بنجاح.' : 'Profile updated.');
    }

    public function storeChild(Request $request)
    {
        $parent = $request->user();
        abort_unless($parent->role === 'parent' || $parent->isParent(), 403);

        $validated = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'child_password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'child_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'child_academic_grade_id' => ['nullable', 'integer'],
            'child_preferred_curriculum_type' => ['required', 'string', 'max:40'],
        ]);

        if (strcasecmp((string) $validated['child_email'], (string) $parent->email) === 0) {
            return back()->withErrors([
                'child_email' => app()->getLocale() === 'ar'
                    ? 'استخدم بريداً خاصاً بالابن. بريد ولي الأمر يبقى لحساب واحد فقط.'
                    : 'Use a separate email for the child. The parent email stays on one account.',
            ])->withInput();
        }

        $years = \App\Support\StudentLearningProfile::publicYears();
        $yearId = (int) $validated['child_academic_year_id'];
        if (! $years->contains('id', $yearId)) {
            return back()->withErrors(['child_academic_year_id' => app()->getLocale() === 'ar' ? 'المرحلة غير متاحة.' : 'That stage is not available.'])->withInput();
        }

        $grades = \App\Support\StudentLearningProfile::gradesByYearMap($years);
        $gradeOptions = $grades[(string) $yearId] ?? [];
        $gradeId = $request->filled('child_academic_grade_id') ? (int) $request->input('child_academic_grade_id') : null;
        if ($gradeOptions !== []) {
            $ids = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $gradeOptions);
            if (! $gradeId || ! in_array($gradeId, $ids, true)) {
                return back()->withErrors(['child_academic_grade_id' => app()->getLocale() === 'ar' ? 'اختر الصف.' : 'Choose the grade.'])->withInput();
            }
        } else {
            $gradeId = null;
        }

        $curriculum = strtolower(trim((string) $validated['child_preferred_curriculum_type']));
        if (! in_array($curriculum, \App\Support\HesetakMatchCatalog::allowedCurriculumTypeKeys(), true)) {
            return back()->withErrors(['child_preferred_curriculum_type' => app()->getLocale() === 'ar' ? 'نوع المنهج غير صالح.' : 'Invalid curriculum type.'])->withInput();
        }

        $payload = [
            'name' => $validated['child_name'],
            'email' => $validated['child_email'],
            'phone' => 'kid.'.$parent->id.'.'.bin2hex(random_bytes(4)),
            'password' => Hash::make($validated['child_password']),
            'role' => 'student',
            'parent_id' => $parent->id,
            'is_active' => true,
            'timezone' => $parent->timezone,
            'academic_year_id' => $yearId,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_grade_id')) {
            $payload['academic_grade_id'] = $gradeId;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'preferred_curriculum_type')) {
            $payload['preferred_curriculum_type'] = $curriculum;
        }

        \App\Models\User::create($payload);

        return back()->with('success', app()->getLocale() === 'ar'
            ? 'تمت إضافة الابن وربطه بحسابك. يدخل لاحقاً ببريده الخاص.'
            : 'The child was added to your account and can sign in with their own email.');
    }
}