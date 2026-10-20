<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGrade;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicGradeController extends Controller
{
    public function store(Request $request, AcademicYear $academicYear)
    {
        $data = $this->validated($request, $academicYear);

        $maxOrder = (int) $academicYear->grades()->max('order');
        $academicYear->grades()->create([
            'name' => trim($data['name']),
            'order' => $data['order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.academic-years.edit', $academicYear->id)
            ->with('success', 'تم إضافة الصف.');
    }

    public function update(Request $request, AcademicYear $academicYear, AcademicGrade $grade)
    {
        $this->assertGradeBelongsToYear($academicYear, $grade);
        $data = $this->validated($request, $academicYear, $grade);

        $grade->update([
            'name' => trim($data['name']),
            'order' => $data['order'] ?? $grade->order,
            'is_active' => $request->boolean('is_active', $grade->is_active),
        ]);

        return redirect()
            ->route('admin.academic-years.edit', $academicYear->id)
            ->with('success', 'تم تحديث الصف.');
    }

    public function destroy(AcademicYear $academicYear, AcademicGrade $grade)
    {
        $this->assertGradeBelongsToYear($academicYear, $grade);
        $grade->delete();

        return redirect()
            ->route('admin.academic-years.edit', $academicYear->id)
            ->with('success', 'تم حذف الصف.');
    }

    private function validated(Request $request, AcademicYear $academicYear, ?AcademicGrade $grade = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_grades', 'name')
                    ->where('academic_year_id', $academicYear->id)
                    ->ignore($grade?->id),
            ],
            'order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم الصف مطلوب',
            'name.unique' => 'هذا الصف موجود بالفعل في المرحلة',
        ]);
    }

    private function assertGradeBelongsToYear(AcademicYear $academicYear, AcademicGrade $grade): void
    {
        abort_unless((int) $grade->academic_year_id === (int) $academicYear->id, 404);
    }
}
