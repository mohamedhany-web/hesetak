@extends('layouts.admin')

@section('title', 'مواعيد مقابلات التوظيف')
@section('page_title', 'مواعيد المقابلات')

@section('content')
<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-xl border border-line bg-surface px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-danger/30 bg-surface px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
        <h3 class="text-base font-semibold text-ink">إضافة موعد متاح</h3>
        <form method="POST" action="{{ route('admin.tutor-interview-slots.store') }}" class="mt-4 grid gap-3 md:grid-cols-2">
            @csrf
            <div>
                <label class="text-xs text-muted">البداية</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-muted">النهاية (اختياري — افتراضي {{ $duration }} دقيقة)</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-muted">السعة</label>
                <input type="number" name="capacity" min="1" max="20" value="{{ old('capacity', 1) }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-muted">نوع الرابط</label>
                <select name="meeting_mode" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
                    <option value="livekit">Hissatak Meeting</option>
                    <option value="external">رابط خارجي</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-muted">رابط خارجي (إن وُجد)</label>
                <input type="url" name="external_url" value="{{ old('external_url') }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm" dir="ltr" placeholder="https://...">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-muted">عنوان / ملاحظات</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm" placeholder="مقابلة تقنية">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_open" value="1" checked> مفتوح للحجز</label>
            <div class="md:col-span-2">
                <button class="rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-white">حفظ الموعد</button>
            </div>
        </form>
    </article>

    <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-line text-muted">
                    <th class="py-2 text-right">البداية</th>
                    <th class="py-2 text-right">النهاية</th>
                    <th class="py-2 text-right">الحجوزات</th>
                    <th class="py-2 text-right">النوع</th>
                    <th class="py-2 text-right">الحالة</th>
                    <th class="py-2 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                    <tr class="border-b border-line/70">
                        <td class="py-2" dir="ltr">{{ $slot->starts_at?->format('Y-m-d H:i') }}</td>
                        <td class="py-2" dir="ltr">{{ $slot->ends_at?->format('Y-m-d H:i') }}</td>
                        <td class="py-2">{{ $slot->booked_count ?? 0 }} / {{ $slot->capacity }}</td>
                        <td class="py-2">{{ $slot->modeLabel() }}</td>
                        <td class="py-2">{{ $slot->is_open ? 'مفتوح' : 'مغلق' }}</td>
                        <td class="py-2">
                            <form method="POST" action="{{ route('admin.tutor-interview-slots.destroy', $slot) }}" onsubmit="return confirm('حذف؟')">
                                @csrf @method('DELETE')
                                <button class="text-danger text-xs font-semibold">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-muted">لا مواعيد بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $slots->links() }}</div>
    </article>
</div>
@endsection
