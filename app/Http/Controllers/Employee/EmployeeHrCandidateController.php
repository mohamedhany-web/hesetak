<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\HrCandidate;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeHrCandidateController extends Controller
{
    private function gate(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isEmployee() && $u->employeeCan('hr_desk'), 403);
    }

    public function index(Request $request)
    {
        $this->gate();

        $query = HrCandidate::query()->withCount('applications')->latest();

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            if ($s !== '') {
                $like = '%'.$s.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            }
        }

        $candidates = $query->paginate(20)->withQueryString();

        return view('employee.hr.recruitment.candidates.index', compact('candidates'));
    }

    public function create()
    {
        $this->gate();

        return view('employee.hr.recruitment.candidates.create');
    }

    public function store(Request $request)
    {
        $this->gate();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:64',
            'portfolio_url' => 'nullable|url|max:500',
            'source' => ['required', Rule::in(array_keys(HrCandidate::sourceLabels()))],
            'notes' => 'nullable|string|max:10000',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:'.config('upload_limits.max_upload_kb'),
        ]);

        $validated['created_by'] = Auth::id();
        $cvPath = null;
        if ($request->hasFile('cv')) {
            $cvPath = PublicMediaStorage::storeFile($request->file('cv'), 'hr_cvs');
        }

        $candidate = HrCandidate::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'source' => $validated['source'],
            'notes' => $validated['notes'] ?? null,
            'cv_path' => $cvPath,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('employee.hr.recruitment.candidates.show', $candidate)
            ->with('success', 'تم إضافة المرشح.');
    }

    public function show(HrCandidate $candidate)
    {
        $this->gate();

        $candidate->load([
            'applications.opening:id,title,status',
        ]);

        $appliedOpeningIds = $candidate->applications->pluck('hr_job_opening_id');

        $openingsForSelect = \App\Models\HrJobOpening::query()
            ->where('status', \App\Models\HrJobOpening::STATUS_OPEN)
            ->when($appliedOpeningIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $appliedOpeningIds))
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('employee.hr.recruitment.candidates.show', compact('candidate', 'openingsForSelect'));
    }

    public function edit(HrCandidate $candidate)
    {
        $this->gate();

        return view('employee.hr.recruitment.candidates.edit', compact('candidate'));
    }

    public function update(Request $request, HrCandidate $candidate)
    {
        $this->gate();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:64',
            'portfolio_url' => 'nullable|url|max:500',
            'source' => ['required', Rule::in(array_keys(HrCandidate::sourceLabels()))],
            'notes' => 'nullable|string|max:10000',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:'.config('upload_limits.max_upload_kb'),
        ]);

        $data = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'source' => $validated['source'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('cv')) {
            $data['cv_path'] = PublicMediaStorage::storeFile(
                $request->file('cv'),
                'hr_cvs',
                $candidate->cv_path
            );
        }

        $candidate->update($data);

        return redirect()->route('employee.hr.recruitment.candidates.show', $candidate)
            ->with('success', 'تم حفظ بيانات المرشح.');
    }

    public function destroy(HrCandidate $candidate)
    {
        $this->gate();

        abort_if($candidate->applications()->exists(), 403, 'لا يمكن حذف مرشح مرتبط بطلبات توظيف.');

        PublicMediaStorage::delete($candidate->cv_path);

        $candidate->delete();

        return redirect()->route('employee.hr.recruitment.candidates.index')
            ->with('success', 'تم حذف المرشح.');
    }
}
