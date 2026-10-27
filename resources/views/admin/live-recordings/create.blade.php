@extends('layouts.admin')
@section('title', 'إضافة تسجيل جلسة')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.live-recordings.index') }}" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 transition-colors"><i class="fas fa-arrow-right"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white"><i class="fas fa-plus-circle text-emerald-500 ml-2"></i>إضافة تسجيل جلسة (R2 / يدوي)</h1>
    </div>

    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-800 dark:text-amber-200">
        <p class="font-semibold mb-1">تسجيل تلقائي من Jibri → R2:</p>
        <p class="mb-2">بعد رفع الفيديو إلى R2 من سكربت Jibri، استدعِ الويب هوك:</p>
        <code class="block bg-white dark:bg-slate-800 p-2 rounded text-xs break-all">curl -X POST {{ url('/api/live-recordings/register') }} \<br>
  -H "X-Webhook-Token: YOUR_TOKEN" \<br>
  -H "Content-Type: application/json" \<br>
  -d '{"session_id": 1, "file_path": "live-recordings/session-1/rec.mp4", "duration_seconds": 3600, "file_size": 104857600}'</code>
        <p class="mt-2 text-xs">اضبط <code>LIVE_RECORDINGS_WEBHOOK_TOKEN</code> في .env ثم استخدمه في الهيدر.</p>
    </div>

    <form method="POST" action="{{ route('admin.live-recordings.store') }}" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        @csrf
        <div class="grid md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">الجلسة <span class="text-red-500">*</span></label>
                <select name="session_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">اختر الجلسة</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ old('session_id') == $s->id ? 'selected' : '' }}>{{ $s->title }} ({{ $s->scheduled_at?->format('Y-m-d H:i') }})</option>
                    @endforeach
                </select>
                @error('session_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">عنوان التسجيل</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="اختياري — يُستخدم عنوان الجلسة إن تُرك فارغاً" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">مكان التخزين (عند الإدخال اليدوي)</label>
                <select name="storage_disk" id="storage_disk" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="r2" {{ old('storage_disk', 'r2') === 'r2' ? 'selected' : '' }}>Cloudflare R2 (بعد رفع Jibri)</option>
                    <option value="local" {{ old('storage_disk') === 'local' ? 'selected' : '' }}>محلي (storage)</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">رفع ملف فيديو (اختياري)</label>
                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov"
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-white">
                <p class="text-xs text-slate-500 mt-1">إن رفعت ملفاً يُخزَّن تلقائياً (R2 إن وُجد إعداد، وإلا محلياً) ولا يلزم إدخال مسار يدوي.</p>
                @error('video')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">مسار الملف في R2 أو storage</label>
                <input type="text" name="file_path" value="{{ old('file_path') }}" placeholder="مثال: live-recordings/session-1/2025-03-16-rec.mp4" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                <p class="text-xs text-slate-500 mt-1">المفتاح (key) كما ظهر بعد الرفع إلى R2، أو المسار النسبي داخل storage للمحلي.</p>
                @error('file_path')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">رابط خارجي (بديل)</label>
                <input type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                <p class="text-xs text-slate-500 mt-1">إن وُجد يُستخدم للمشاهدة بدل file_path.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">المدة (ثانية)</label>
                <input type="number" name="duration_seconds" value="{{ old('duration_seconds', 0) }}" min="0" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">حجم الملف (بايت)</label>
                <input type="number" name="file_size" value="{{ old('file_size', 0) }}" min="0" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="rounded text-emerald-500">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">نشر التسجيل للطلاب فوراً</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition-colors"><i class="fas fa-save ml-1"></i> إضافة التسجيل</button>
            <a href="{{ route('admin.live-recordings.index') }}" class="px-6 py-2.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-300 transition-colors">إلغاء</a>
        </div>
    </form>
</div>
@endsection
