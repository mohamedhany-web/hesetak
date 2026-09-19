@extends(!empty($useInstructorRoutes) ? 'layouts.app' : 'layouts.student-timeline')

@section('title', 'إنشاء اجتماع جديد')
@section('header', 'إنشاء اجتماع جديد')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-6">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">إنشاء اجتماع</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">تحكم كامل في عنوان الاجتماع وحدود المشاركين قبل البدء.</p>
    </div>

    <form action="{{ route('student.classroom.store') }}" method="POST" class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">عنوان الاجتماع</label>
            <input type="text" name="title" value="{{ old('title', 'غرفة حصتك - ' . now()->format('H:i')) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white">
            @error('title')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الحد الأقصى للمشاركين</label>
            <input type="number" min="2" max="{{ (int) $limits['classroom_max_participants'] }}" name="max_participants" value="{{ old('max_participants', (int) $limits['classroom_max_participants']) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">الحد الأقصى حسب باقتك: {{ (int) $limits['classroom_max_participants'] }}</p>
            @error('max_participants')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">وضع البداية</label>
            <select name="start_now" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white">
                <option value="1" {{ old('start_now', '1') === '1' ? 'selected' : '' }}>ابدأ الآن مباشرة</option>
                <option value="0" {{ old('start_now') === '0' ? 'selected' : '' }}>إنشاء كمجدول فقط</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">موعد الاجتماع (اختياري عند الجدولة)</label>
            <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">إذا اخترت "إنشاء كمجدول فقط" يفضل تحديد موعد هنا.</p>
            @error('scheduled_for')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">مدة الاجتماع (بالدقائق)</label>
            <input type="number" min="15" max="{{ (int) $limits['classroom_max_duration_minutes'] }}" name="planned_duration_minutes" value="{{ old('planned_duration_minutes', (int) $limits['classroom_default_duration_minutes']) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">الافتراضي حسب الباقة: {{ (int) $limits['classroom_default_duration_minutes'] }} دقيقة — الحد الأقصى: {{ (int) $limits['classroom_max_duration_minutes'] }} دقيقة.</p>
            @error('planned_duration_minutes')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600 p-3 text-xs text-slate-600 dark:text-slate-300">
            استهلاك الشهر الحالي: {{ number_format($usedMeetingsThisMonth) }} من {{ number_format($limits['classroom_meetings_per_month']) }}.
            المتبقي: <span class="font-bold">{{ number_format($remainingMeetingsThisMonth) }}</span>
        </div>

        <div class="flex items-center gap-2 justify-end">
            <a href="{{ route('student.classroom.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300">إلغاء</a>
            <button type="submit" {{ $remainingMeetingsThisMonth <= 0 ? 'disabled' : '' }} class="px-5 py-2 rounded-xl {{ $remainingMeetingsThisMonth <= 0 ? 'bg-slate-400 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600' }} text-white font-semibold">إنشاء الاجتماع</button>
        </div>
    </form>
</div>
@endsection

