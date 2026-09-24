@extends('layouts.admin')

@section('title', 'إعدادات مسار التوظيف')
@section('page_title', 'تعديل بروسيس التوظيف')

@section('content')
@php $s = $settings; @endphp
<div class="max-w-3xl space-y-5">
    @if(session('success'))
        <div class="rounded-xl border border-line bg-surface px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.hiring.settings.update') }}" class="space-y-5 rounded-2xl border border-line bg-surface p-5 shadow-soft">
        @csrf
        @method('PUT')

        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_specialty" value="1" @checked(!empty($s['require_specialty']))> إلزام تخصص (مادة + منهج)</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_interview" value="1" @checked(!empty($s['require_interview']))> إلزام مقابلة تقنية</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_contract" value="1" @checked(!empty($s['require_contract']))> إلزام توقيع عقد قبل التفعيل</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_reschedule" value="1" @checked(!empty($s['allow_reschedule']))> السماح بإعادة جدولة المقابلة</label>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="text-xs text-muted">مدة المقابلة (دقيقة)</label>
                <input type="number" name="interview_duration_minutes" value="{{ old('interview_duration_minutes', $s['interview_duration_minutes'] ?? 30) }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-muted">سماح التغيب قبل الحجب (دقيقة)</label>
                <input type="number" name="no_show_grace_minutes" value="{{ old('no_show_grace_minutes', $s['no_show_grace_minutes'] ?? 15) }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="text-xs text-muted">عنوان إيميل المقابلة</label>
            <input type="text" name="interview_email_subject" value="{{ old('interview_email_subject', $s['interview_email_subject'] ?? '') }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-muted">نص إيميل المقابلة — placeholders: {name} {datetime} {join_url} {app_name}</label>
            <textarea name="interview_email_body" rows="5" class="w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('interview_email_body', $s['interview_email_body'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="text-xs text-muted">نص واتساب المقابلة</label>
            <textarea name="interview_whatsapp_body" rows="4" class="w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('interview_whatsapp_body', $s['interview_whatsapp_body'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="text-xs text-muted">عنوان إيميل العقد</label>
            <input type="text" name="contract_email_subject" value="{{ old('contract_email_subject', $s['contract_email_subject'] ?? '') }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-muted">نص إيميل العقد — {name} {sign_url} {app_name}</label>
            <textarea name="contract_email_body" rows="4" class="w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('contract_email_body', $s['contract_email_body'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="text-xs text-muted">نص واتساب العقد</label>
            <textarea name="contract_whatsapp_body" rows="3" class="w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('contract_whatsapp_body', $s['contract_whatsapp_body'] ?? '') }}</textarea>
        </div>

        <button class="rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white">حفظ الإعدادات</button>
    </form>
</div>
@endsection
