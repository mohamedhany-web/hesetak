@extends('layouts.admin')

@section('title', 'طلب مدرب جديد')
@section('header', 'إنشاء طلب مدرب')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="rounded-3xl bg-white border border-slate-200 shadow-lg p-6 sm:p-8">
        <form action="{{ route('admin.instructor-requests.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-2">المدرب <span class="text-red-500">*</span></label>
                <select name="instructor_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                    <option value="">اختر المدرب...</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected(old('instructor_id') == $instructor->id)>{{ $instructor->name }} — {{ $instructor->email }}</option>
                    @endforeach
                </select>
                @error('instructor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-2">الموضوع <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                       class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm" placeholder="موضوع الطلب">
                @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-2">الرسالة <span class="text-red-500">*</span></label>
                <textarea name="message" rows="6" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="تفاصيل الطلب...">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium">حفظ الطلب</button>
                <a href="{{ route('admin.instructor-requests.index') }}" class="px-6 py-2.5 border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
