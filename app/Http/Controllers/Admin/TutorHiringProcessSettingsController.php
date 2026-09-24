<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorHiringSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorHiringProcessSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = TutorHiringSetting::allMapped();

        return view('admin.hiring.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'require_interview' => ['nullable', 'boolean'],
            'require_contract' => ['nullable', 'boolean'],
            'require_specialty' => ['nullable', 'boolean'],
            'allow_reschedule' => ['nullable', 'boolean'],
            'interview_duration_minutes' => ['required', 'integer', 'min:10', 'max:180'],
            'no_show_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'interview_email_subject' => ['nullable', 'string', 'max:190'],
            'interview_email_body' => ['nullable', 'string', 'max:5000'],
            'interview_whatsapp_body' => ['nullable', 'string', 'max:2000'],
            'contract_email_subject' => ['nullable', 'string', 'max:190'],
            'contract_email_body' => ['nullable', 'string', 'max:5000'],
            'contract_whatsapp_body' => ['nullable', 'string', 'max:2000'],
        ]);

        TutorHiringSetting::putMany([
            'require_interview' => $request->boolean('require_interview'),
            'require_contract' => $request->boolean('require_contract'),
            'require_specialty' => $request->boolean('require_specialty'),
            'allow_reschedule' => $request->boolean('allow_reschedule'),
            'interview_duration_minutes' => (int) $data['interview_duration_minutes'],
            'no_show_grace_minutes' => (int) $data['no_show_grace_minutes'],
            'interview_email_subject' => $data['interview_email_subject'] ?? '',
            'interview_email_body' => $data['interview_email_body'] ?? '',
            'interview_whatsapp_body' => $data['interview_whatsapp_body'] ?? '',
            'contract_email_subject' => $data['contract_email_subject'] ?? '',
            'contract_email_body' => $data['contract_email_body'] ?? '',
            'contract_whatsapp_body' => $data['contract_whatsapp_body'] ?? '',
        ]);

        return back()->with('success', 'تم حفظ إعدادات مسار التوظيف.');
    }
}
