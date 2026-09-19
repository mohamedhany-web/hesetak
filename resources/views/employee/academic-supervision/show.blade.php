@extends('layouts.employee')

@section('title', 'طالب: '.$student->name)
@section('header', 'متابعة الطالب')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ $student->name }}</h2>
            <p class="text-sm text-gray-600">{{ $student->email }} @if($student->phone)<span class="text-gray-400">·</span> {{ $student->phone }}@endif</p>
            <p class="text-xs text-gray-500 mt-1">آخر ظهور: {{ $student->last_login_at ? $student->last_login_at->format('Y-m-d H:i') : 'غير متوفر' }}</p>
        </div>
        <a href="{{ route('employee.academic-supervision.index') }}" class="text-sm font-semibold text-teal-700 hover:underline">← العودة للقائمة</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 mb-3">الاشتراك والميزات</h3>
            @if($subscription)
                <p class="text-sm text-gray-800"><span class="text-gray-500">الباقة:</span> {{ $subscription->plan_name ?: '—' }}</p>
                <p class="text-sm text-gray-800 mt-1"><span class="text-gray-500">الحالة:</span> {{ $subscription->status }}</p>
                <p class="text-sm text-gray-800 mt-1"><span class="text-gray-500">حتى:</span> {{ $subscription->end_date?->format('Y-m-d') ?? '—' }}</p>
            @else
                <p class="text-sm text-amber-700">لا يوجد اشتراك نشط حالياً.</p>
            @endif
            <p class="text-sm mt-3">
                <span class="text-gray-500">فصول حصتك:</span>
                @if($hasClassroom)
                    <span class="font-semibold text-emerald-700">مفعّل</span>
                @else
                    <span class="font-semibold text-gray-600">غير مفعّل في الباقة</span>
                @endif
            </p>
            @if($hasClassroom)
                <p class="text-xs text-gray-500 mt-2">ميتينج هذا الشهر: {{ $usedMeetingsThisMonth }} / {{ (int) $limits['classroom_meetings_per_month'] }} — الحد الأقصى للحضور: {{ (int) $limits['classroom_max_participants'] }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 mb-3">الكورسات</h3>
            <p class="text-2xl font-black text-teal-900 tabular-nums">{{ $student->course_enrollments_count }}</p>
            <p class="text-xs text-gray-500">عدد التسجيلات في الكورسات</p>
        </div>
    </div>

    @if($liveMeeting)
        <div class="rounded-2xl border-2 border-emerald-300/60 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-emerald-800 uppercase tracking-wide mb-1">ميتينج نشط الآن</p>
                <p class="font-bold text-gray-900">{{ $liveMeeting->title ?: $liveMeeting->code }}</p>
                <p class="text-sm text-gray-600 mt-1">الحضور الحالي: {{ $liveMeeting->participants_count }} — الرمز: {{ $liveMeeting->code }}</p>
            </div>
            <a href="{{ route('employee.academic-supervision.meeting.observe', $liveMeeting) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500 shadow-md">
                <i class="fas fa-video"></i> دخول مراقبة الغرفة
            </a>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">تسجيلات الكورسات</h3>
        </div>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 font-semibold sticky top-0">
                    <tr>
                        <th class="text-right px-4 py-2">الكورس</th>
                        <th class="text-right px-4 py-2">الحالة</th>
                        <th class="text-right px-4 py-2">التقدم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($enrollments as $en)
                        <tr>
                            <td class="px-4 py-2">{{ $en->course?->title ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $en->status ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $en->progress !== null ? number_format((float) $en->progress, 1).'%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">لا توجد تسجيلات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">اجتماعات Classroom (الطالب كمضيف)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                    <tr>
                        <th class="text-right px-4 py-2">العنوان</th>
                        <th class="text-right px-4 py-2">الحالة</th>
                        <th class="text-right px-4 py-2">الحضور (ذروة)</th>
                        <th class="text-right px-4 py-2 w-32"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($meetings as $m)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $m->title ?: $m->code }}</td>
                            <td class="px-4 py-2">
                                @if($m->isLive())
                                    <span class="text-emerald-700 font-bold text-xs">لايف</span>
                                @elseif($m->ended_at)
                                    <span class="text-gray-500 text-xs">انتهى</span>
                                @else
                                    <span class="text-amber-700 text-xs">مجدول</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $m->participants_count }} @if($m->participants_peak) <span class="text-gray-400">(ذروة {{ $m->participants_peak }})</span> @endif</td>
                            <td class="px-4 py-2">
                                @if($m->isLive())
                                    <a href="{{ route('employee.academic-supervision.meeting.observe', $m) }}" class="text-cyan-600 font-semibold text-xs hover:underline">مراقبة</a>
                                @endif
                                <a href="{{ url('classroom/join/'.$m->code) }}" target="_blank" rel="noopener" class="text-gray-500 text-xs mr-2 hover:underline">رابط الدعوة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">لا توجد اجتماعات مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
