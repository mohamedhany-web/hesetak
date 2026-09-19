@extends('layouts.admin')

@section('title', 'استشارات المدربين')
@section('header', 'استشارات المدربين')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">إعدادات الاستشارة الافتراضية</h2>
        <form method="POST" action="{{ route('admin.consultations.settings') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">السعر الافتراضي (ريال سعودي — ر.س)</label>
                <p class="text-[11px] text-slate-500 mb-1">يُستخدم للمدربين الذين لم يُحدَّد لهم سعر خاص من صفحة <a href="{{ route('admin.personal-branding.index') }}" class="text-sky-600 font-semibold underline">التسويق الشخصي</a>.</p>
                <input type="number" step="0.01" name="default_price" value="{{ old('default_price', $settings->default_price) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">المدة الافتراضية (دقيقة)</label>
                <input type="number" name="default_duration_minutes" value="{{ old('default_duration_minutes', $settings->default_duration_minutes) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1">تعليمات الدفع (تظهر للطالب)</label>
                <textarea name="payment_instructions" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="رقم الآيبان، اسم البنك، ملاحظات...">{{ old('payment_instructions', $settings->payment_instructions) }}</textarea>
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded border-slate-300" {{ old('is_active', $settings->is_active ? '1' : '') ? 'checked' : '' }}>
                <label for="is_active" class="text-sm text-slate-700">تفعيل طلبات الاستشارة من صفحة المدربين العامة</label>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold">حفظ الإعدادات</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="rounded-xl bg-white border border-slate-200 p-4"><p class="text-xs text-slate-500">بانتظار الدفع</p><p class="text-2xl font-bold">{{ $stats['pending'] }}</p></div>
        <div class="rounded-xl bg-white border border-slate-200 p-4"><p class="text-xs text-slate-500">أبلغ عن تحويل</p><p class="text-2xl font-bold text-amber-700">{{ $stats['payment_reported'] }}</p></div>
        <div class="rounded-xl bg-white border border-violet-200 bg-violet-50/50 p-4"><p class="text-xs text-violet-700 font-semibold">محفظة — بانتظاركم</p><p class="text-2xl font-bold text-violet-800">{{ $stats['awaiting_verification'] }}</p></div>
        <div class="rounded-xl bg-white border border-slate-200 p-4"><p class="text-xs text-slate-500">دفع مؤكد</p><p class="text-2xl font-bold text-sky-700">{{ $stats['paid'] }}</p></div>
        <div class="rounded-xl bg-white border border-slate-200 p-4"><p class="text-xs text-slate-500">مجدولة</p><p class="text-2xl font-bold text-emerald-700">{{ $stats['scheduled'] }}</p></div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
            <form method="GET" class="flex flex-wrap gap-2">
                <select name="status" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>كل الحالات</option>
                    @foreach(\App\Models\ConsultationRequest::statusLabels() as $key => $label)
                        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold">تصفية</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">#</th>
                        <th class="px-4 py-3 text-right">الطالب</th>
                        <th class="px-4 py-3 text-right">المدرب</th>
                        <th class="px-4 py-3 text-right">المبلغ</th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">التاريخ</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $req)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $req->id }}</td>
                            <td class="px-4 py-3">{{ $req->student->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $req->instructor->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format($req->price_amount, 2) }} $</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 rounded-md bg-slate-100 text-xs">{{ $req->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3"><a href="{{ route('admin.consultations.show', $req) }}" class="text-sky-600 font-semibold hover:underline">إدارة</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">لا توجد طلبات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
