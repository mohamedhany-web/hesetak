<x-mail::message>
# تقرير عائلة حصتك

مرحباً،

هذا ملخص مبسّط لتقدّم **{{ $student->name }}** خلال **{{ $payload['period']['label'] ?? '' }}**.

## مؤشرات هذا الشهر
- متوسط الاختبارات: **{{ $payload['current']['exam_average'] ?? '—' }}%**
@if(isset($payload['deltas']['exam_average']) && $payload['deltas']['exam_average'] !== null)
  (التغيير عن الشهر السابق: {{ $payload['deltas']['exam_average'] > 0 ? '+' : '' }}{{ $payload['deltas']['exam_average'] }})
@endif
- الحضور: **{{ $payload['current']['attendance_percent'] ?? '—' }}%**
- حصص مكتملة: **{{ $payload['current']['sessions_completed'] ?? 0 }}**
- تقدّم الكورسات: **{{ $payload['current']['course_progress_percent'] ?? '—' }}%**

## نقاط القوة
@foreach(($payload['strengths'] ?? []) as $s)
- {{ $s }}
@endforeach

## مؤشرات التحسّن
@forelse(($payload['improvements'] ?? []) as $i)
- {{ $i }}
@empty
- لا توجد تنبيهات حرجة هذا الشهر.
@endforelse

@if(!empty($payload['suggestions']))
## اقتراحات للمراجعة
@foreach($payload['suggestions'] as $sug)
- **{{ $sug['title'] }}** — {{ $sug['reason'] ?? '' }}
@endforeach
@endif

<x-mail::button :url="$payload['share_url'] ?? url('/')">
فتح التقرير التفصيلي
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
