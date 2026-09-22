<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! student_ui('show_certificates', false)) {
                abort(404);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $certificates = Certificate::where('user_id', auth()->id())
            ->with('course')
            ->where(function($q) {
                $q->where('status', 'issued')->orWhere('is_verified', true);
            })
            ->orderByRaw('COALESCE(issued_at, issue_date) DESC')
            ->paginate(15);

        $stats = [
            'total' => $certificates->total(),
            'issued' => Certificate::where('user_id', auth()->id())
                ->where(function($q) {
                    $q->where('status', 'issued')->orWhere('is_verified', true);
                })->count(),
        ];

        return view('student.certificates.index', compact('certificates', 'stats'));
    }

    public function show(Certificate $certificate)
    {
        if ($certificate->user_id !== auth()->id()) {
            abort(403);
        }

        $certificate->load(['course']);
        return view('student.certificates.show', compact('certificate'));
    }

    public function file(Certificate $certificate)
    {
        if ($certificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (empty($certificate->pdf_path)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($certificate->pdf_path)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($certificate->pdf_path, PATHINFO_EXTENSION));
        $downloadName = 'certificate-' . ($certificate->certificate_number ?? $certificate->id) . '.' . ($ext ?: 'pdf');

        // inline preview in browser (PDF/images) + download supported by the browser UI
        return $disk->response($certificate->pdf_path, $downloadName, [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }
}
