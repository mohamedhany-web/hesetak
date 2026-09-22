<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
