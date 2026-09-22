<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HesetakCurriculumType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HesetakCurriculumTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.personal-branding') && ! $user->hasPermission('manage.academic-years'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        $types = HesetakCurriculumType::query()->ordered()->get();

        return view('admin.marketing.curriculum-types.index', [
            'types' => $types,
            'stats' => [
                'total' => $types->count(),
                'active' => $types->where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        HesetakCurriculumType::query()->create([
            'key' => $data['key'],
            'label_ar' => $data['label_ar'],
            'label_en' => $data['label_en'],
            'aliases' => $data['aliases'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return back()->with('success', 'تمت إضافة نوع المنهج. يظهر الآن للمعلمين وفي دليل الطلاب.');
    }

    public function update(Request $request, HesetakCurriculumType $curriculumType): RedirectResponse
    {
        $data = $this->validated($request, $curriculumType);

        $curriculumType->update([
            'key' => $data['key'],
            'label_ar' => $data['label_ar'],
            'label_en' => $data['label_en'],
            'aliases' => $data['aliases'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return back()->with('success', 'تم تحديث نوع المنهج.');
    }

    public function destroy(HesetakCurriculumType $curriculumType): RedirectResponse
    {
        $curriculumType->delete();

        return back()->with('success', 'تم حذف نوع المنهج.');
    }

    public function toggle(HesetakCurriculumType $curriculumType): RedirectResponse
    {
        $curriculumType->update(['is_active' => ! $curriculumType->is_active]);

        return back()->with('success', $curriculumType->is_active ? 'تم تفعيل النوع.' : 'تم إيقاف النوع.');
    }

    /**
     * @return array{key:string,label_ar:string,label_en:string,aliases:list<string>,sort_order:int,is_active:bool}
     */
    private function validated(Request $request, ?HesetakCurriculumType $existing = null): array
    {
        $data = $request->validate([
            'key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('hesetak_curriculum_types', 'key')->ignore($existing?->id),
            ],
            'label_ar' => ['required', 'string', 'max:120'],
            'label_en' => ['required', 'string', 'max:120'],
            'aliases' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'key.regex' => 'المفتاح بالإنجليزية الصغيرة وأرقام وشرطة سفلية فقط (مثال: saudi).',
        ]);

        $key = Str::lower(trim($data['key']));
        $aliasesRaw = preg_split('/[\r\n,،|]+/u', (string) ($data['aliases'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $aliases = array_values(array_unique(array_filter(array_map('trim', $aliasesRaw))));

        return [
            'key' => $key,
            'label_ar' => trim($data['label_ar']),
            'label_en' => trim($data['label_en']),
            'aliases' => $aliases,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
