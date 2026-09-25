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
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

        return view('student.profile.index', compact('user', 'profileImageUrl', 'academicYears', 'curriculumTypes'));
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
            'preferred_curriculum_type' => 'nullable|string|max:40',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
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

        return back()->with('success', 'تم تحديث البروفايل بنجاح');
    }
}