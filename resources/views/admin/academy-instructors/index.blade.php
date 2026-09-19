@extends('layouts.admin')

@section('title', 'مدربو الأكاديمية - حصتك')
@section('page_title', 'مدربو الأكاديمية')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الأكاديمية · التوصيف والتغطية</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مدربو الأكاديمية</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">
                من هنا ترى من يدير مجموعات جماعية أو فردية، ومن له كورسات، وتوصّف يدوياً مدرّباً لطالب.
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">المدربون</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $summary['instructors'] }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مجموعات جماعية</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $summary['collective'] }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مجموعات فردية</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $summary['individual'] }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">توصيفات نشطة</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $summary['assignments'] }}</p>
        </article>
    </section>

    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <input type="search" name="q" value="{{ $search }}" placeholder="بحث بالاسم أو البريد…"
               class="h-11 min-w-[220px] flex-1 rounded-xl border border-line bg-surface px-4 text-sm text-ink">
        <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-5 text-sm font-medium text-white">بحث</button>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المدرّب</th>
                        <th class="px-4 py-3 text-start font-medium">جماعي</th>
                        <th class="px-4 py-3 text-start font-medium">فردي</th>
                        <th class="px-4 py-3 text-start font-medium">كورسات</th>
                        <th class="px-4 py-3 text-start font-medium">طلاب موصّفون</th>
                        <th class="px-4 py-3 text-start font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($rows as $row)
                        @php $ins = $row['instructor']; @endphp
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $ins->name }}</p>
                                <p class="text-xs text-muted">{{ $ins->email }}</p>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $row['collective_groups'] }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $row['individual_groups'] }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $row['courses'] }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $row['assigned_students'] }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.academy-instructors.show', $ins) }}" class="text-xs font-semibold text-accent hover:underline">التفاصيل والتوصيف</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-muted">لا يوجد مدربون.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
