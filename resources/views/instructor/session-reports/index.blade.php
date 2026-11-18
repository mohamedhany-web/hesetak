@extends('layouts.app')

@section('title', 'تقارير الحصص')
@section('page_title', 'تقارير الحصص')

@section('content')
<div class="id-page">
    @if(session('success'))
        <div class="id-alert id-alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif

    <section class="id-hero" aria-label="تقارير الحصص">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.private_lessons') }}</p>
            <h2 class="id-hero__title">تقرير الحصة للطالب — إلزامي بعد كل حصة</h2>
            <p class="id-hero__meta">يصل التقرير لحساب الطالب فور إرساله، ويُسجَّل مستحقك حسب اتفاقيتك بعد رفعه.</p>
        </div>
    </section>

    <section class="id-panel id-panel--wide" aria-label="حصص بانتظار التقرير">
        <header class="id-panel__head">
            <h2>حصص بانتظار التقرير ({{ $pending->count() }})</h2>
        </header>
        @if($pending->isEmpty())
            <div class="id-empty" style="border:0;background:transparent;padding:24px 8px">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-check"></i></span>
                <p>لا توجد حصص بانتظار تقرير. أحسنت!</p>
            </div>
        @else
            <div class="id-alert id-alert--err" style="margin-bottom:12px">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span>لديك {{ $pending->count() }} حصة بدون تقرير — رفعها إلزامي، ويمنع طلب السحب حتى تُرفع.</span>
            </div>
            <div class="id-table-wrap">
                <table class="id-table">
                    <thead><tr><th>الطالب</th><th>موعد الحصة</th><th></th></tr></thead>
                    <tbody>
                        @foreach($pending as $s)
                            <tr>
                                <td><strong>{{ $s->student?->name ?? '—' }}</strong></td>
                                <td class="tabular-nums"><x-app-datetime :at="$s->scheduled_at" pattern="Y-m-d · g:i A" /></td>
                                <td class="id-table__end">
                                    <a href="{{ route('instructor.session-reports.edit', $s) }}" class="id-btn id-btn--gold" style="min-height:34px;padding:0 12px;font-size:12px">كتابة التقرير</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="id-panel id-panel--wide" aria-label="التقارير المرسلة">
        <header class="id-panel__head"><h2>التقارير المرسلة</h2></header>
        <div class="id-table-wrap">
            <table class="id-table">
                <thead><tr><th>الطالب</th><th>الحصة</th><th>التقييم</th><th>قراءة الطالب</th><th></th></tr></thead>
                <tbody>
                    @forelse($submitted as $r)
                        <tr>
                            <td><strong>{{ $r->student?->name ?? '—' }}</strong></td>
                            <td class="tabular-nums"><x-app-datetime :at="$r->session?->scheduled_at" pattern="Y-m-d · g:i A" /></td>
                            <td>{{ $r->performanceLabel() ?? '—' }}</td>
                            <td>{{ $r->student_read_at ? 'تمت القراءة' : 'لم يقرأ بعد' }}</td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.session-reports.edit', $r->one_to_one_session_id) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">تعديل</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="id-empty" style="border:0;background:transparent;padding:24px 8px"><p>لم ترسل تقارير بعد.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($submitted->hasPages())
            <div class="id-pager">{{ $submitted->links() }}</div>
        @endif
    </section>
</div>
@endsection
