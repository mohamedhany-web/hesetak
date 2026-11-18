<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\PackageSlotProposal;
use App\Services\PackageSlotProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PackageSlotProposalController extends Controller
{
    public function index(Request $request): View
    {
        $proposals = PackageSlotProposal::query()
            ->where('instructor_id', $request->user()->id)
            ->with(['student:id,name,email', 'servicePackage:id,name', 'academicSubject:id,name'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(15);

        return view('instructor.package-slot-proposals.index', compact('proposals'));
    }

    public function respond(Request $request, PackageSlotProposal $proposal): RedirectResponse
    {
        abort_unless((int) $proposal->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'accepted_slots' => ['nullable', 'array'],
            'accepted_slots.*' => ['string', 'max:64'],
            'response_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $accepted = $data['decision'] === 'accept' ? ($data['accepted_slots'] ?? []) : [];
        if ($data['decision'] === 'accept' && $accepted === []) {
            return back()->with('error', 'اختر موعداً واحداً على الأقل لقبوله، أو اضغط «رفض».');
        }

        try {
            PackageSlotProposalService::respond($proposal, $request->user(), $accepted, $data['response_note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data['decision'] === 'accept'
            ? 'تم قبول المواعيد المحددة وإشعار الطالب.'
            : 'تم رفض الطلب وإشعار الطالب.');
    }
}
