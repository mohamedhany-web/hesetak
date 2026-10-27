<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParentChildController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503, 'عمود parent_id غير متوفر بعد التشغيل.');

        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->withCount('children')
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        } else {
            $query->where(function ($q) {
                $q->where('role', 'parent')
                    ->orWhereHas('children');
            });
        }

        $parents = $query->paginate(20)->withQueryString();

        return view('admin.parents.index', compact('parents', 'search'));
    }

    public function create(): View
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503);

        $students = User::query()
            ->where('role', 'student')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'email']);

        return view('admin.parents.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8', 'max:190'],
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return back()->withInput()->withErrors(['email' => 'أدخل البريد الإلكتروني أو رقم الجوال للبحث أو الإنشاء.']);
        }

        $parent = User::query()
            ->where(function ($q) use ($data) {
                if (! empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
                if (! empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
            })
            ->first();

        if ($parent) {
            if ($parent->isInstructor() || in_array($parent->role, ['super_admin', 'admin'], true)) {
                return back()->withInput()->with('error', 'لا يمكن ترقية هذا الحساب إلى ولي أمر.');
            }
            if ($parent->role !== 'parent') {
                $parent->update(['role' => 'parent']);
            }
            if (! empty($data['name'])) {
                $parent->update(['name' => $data['name']]);
            }
        } else {
            if (empty($data['name']) || empty($data['password'])) {
                return back()->withInput()->withErrors([
                    'name' => 'الاسم مطلوب لإنشاء حساب جديد.',
                    'password' => 'كلمة المرور مطلوبة لإنشاء حساب جديد.',
                ]);
            }
            if (empty($data['email'])) {
                return back()->withInput()->withErrors(['email' => 'البريد الإلكتروني مطلوب لإنشاء حساب جديد.']);
            }

            $parent = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'parent',
                'is_active' => true,
                'uuid' => (string) Str::uuid(),
            ]);
        }

        if (! empty($data['student_id'])) {
            $student = User::query()->findOrFail($data['student_id']);
            if ($student->role !== 'student') {
                return back()->with('error', 'يمكن ربط حسابات الطلاب فقط.');
            }
            $student->update(['parent_id' => $parent->id]);
        }

        return redirect()->route('admin.parents.show', $parent)
            ->with('success', 'تم حفظ ولي الأمر بنجاح.');
    }

    public function show(User $user): View
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503);

        $user->load(['children' => fn ($q) => $q->orderBy('name')]);

        $availableStudents = User::query()
            ->where('role', 'student')
            ->where(function ($q) use ($user) {
                $q->whereNull('parent_id')->orWhere('parent_id', $user->id);
            })
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email', 'parent_id']);

        return view('admin.parents.show', [
            'parent' => $user,
            'availableStudents' => $availableStudents,
        ]);
    }

    public function link(Request $request, User $user): RedirectResponse
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::query()->findOrFail($data['student_id']);
        if ($student->role !== 'student') {
            return back()->with('error', 'يمكن ربط حسابات الطلاب فقط.');
        }
        if ((int) $student->id === (int) $user->id) {
            return back()->with('error', 'لا يمكن ربط المستخدم بنفسه.');
        }

        $student->update(['parent_id' => $user->id]);

        return back()->with('success', 'تم ربط الطالب «'.$student->name.'» بولي الأمر.');
    }

    public function unlink(Request $request, User $user): RedirectResponse
    {
        abort_unless(Schema::hasColumn('users', 'parent_id'), 503);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::query()->findOrFail($data['student_id']);
        if ((int) $student->parent_id !== (int) $user->id) {
            return back()->with('error', 'هذا الطالب غير مرتبط بولي الأمر المحدد.');
        }

        $student->update(['parent_id' => null]);

        return back()->with('success', 'تم فك ارتباط الطالب «'.$student->name.'».');
    }
}
