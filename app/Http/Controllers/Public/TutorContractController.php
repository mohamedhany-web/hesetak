<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InstructorAgreement;
use App\Services\TutorApplicationStorage;
use App\Services\TutorContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TutorContractController extends Controller
{
    public function show(string $token): View
    {
        $agreement = InstructorAgreement::query()
            ->where('offer_token', $token)
            ->with(['tutorApplication', 'instructor'])
            ->firstOrFail();

        return view('tutor.contract-sign', [
            'agreement' => $agreement,
            'application' => $agreement->tutorApplication,
            'token' => $token,
        ]);
    }

    public function sign(Request $request, string $token, TutorContractService $contracts): RedirectResponse
    {
        $agreement = InstructorAgreement::query()
            ->where('offer_token', $token)
            ->firstOrFail();

        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:160'],
            'signature_data' => ['required', 'string'],
        ], [
            'signer_name.required' => 'اكتب الاسم كما سيظهر على العقد.',
            'signature_data.required' => 'التوقيع مطلوب.',
        ]);

        try {
            $contracts->sign(
                $agreement,
                trim($data['signer_name']),
                $data['signature_data'],
                (string) $request->ip(),
                (string) $request->userAgent()
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('tutor.contract.sign', ['token' => $token])
            ->with('success', 'تم توقيع العقد بنجاح. ستصلك نسخة PDF عبر الإيميل إن توفّر، ويمكنك تنزيلها من هذه الصفحة.');
    }

    public function downloadPdf(string $token): Response
    {
        $agreement = InstructorAgreement::query()
            ->where('offer_token', $token)
            ->firstOrFail();

        abort_unless($agreement->isSigned() && filled($agreement->pdf_path), 404);

        $disk = TutorApplicationStorage::resolvedDisk();
        abort_unless(Storage::disk($disk)->exists($agreement->pdf_path), 404);

        $bytes = Storage::disk($disk)->get($agreement->pdf_path);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="hesetak-agreement-'.$agreement->id.'.pdf"',
        ]);
    }
}
