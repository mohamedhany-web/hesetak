@extends('layouts.admin')

@section('title', 'تعديل البرنامج - ' . config('app.name'))
@section('page_title', 'تعديل برنامج')

@section('content')
<div class="space-y-5" x-data="courseForm({ selectedSkills: @json($selectedSkills ?? []) })">
    <div class="w-full max-w-full space-y-5">
        <div class="rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="text-sm text-muted mb-2">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent">{{ __('admin.dashboard') }}</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.advanced-courses.index') }}" class="hover:text-accent">{{ __('admin.courses_management') }}</a>
                        <span class="mx-2">/</span>
                        <span class="text-ink truncate">{{ Str::limit($advancedCourse->title, 30) }}</span>
                    </nav>
                    <h1 class="text-2xl sm:text-3xl font-bold text-ink">تعديل البرنامج التدريبي</h1>
                    <p class="text-sm text-muted mt-1">تحديث معلومات البرنامج والمحتوى والمهارات المستهدفة.</p>
                </div>
                <a href="{{ route('admin.advanced-courses.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-line px-4 py-2.5 text-sm font-semibold text-ink hover:bg-accent-soft hover:text-accent transition">
                    <i class="fas fa-arrow-right"></i>
                    العودة للبرامج
                </a>
            </div>
        </div>

        <form action="{{ route('admin.advanced-courses.update', $advancedCourse) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-line bg-surface shadow-soft">
                        <div class="border-b border-line px-5 py-4">
                            <h2 class="text-lg font-semibold text-ink">المعلومات الأساسية</h2>
                            <p class="text-xs text-muted mt-1">تحديث تفاصيل البرنامج التدريبي.</p>
                        </div>
                        <div class="p-6 sm:p-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2 space-y-2">
                                    <label class="block text-sm font-semibold text-ink">عنوان البرنامج *</label>
                                    <input type="text" name="title" value="{{ old('title', $advancedCourse->title) }}" required
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink shadow-sm focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                           placeholder="مثال: إدارة الصف الفعّال">
                                    @error('title') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">المدرّس المسؤول *</label>
                                    <select name="instructor_id" required
                                            class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                        <option value="">— اختر المدرّس —</option>
                                        @foreach($instructors as $instructor)
                                            <option value="{{ $instructor->id }}" {{ old('instructor_id', $advancedCourse->instructor_id) == $instructor->id ? 'selected' : '' }}>
                                                {{ $instructor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted mt-1">مطلوب للبرامج الجماعية والفردية — يظهر في صفحة المجموعات والتفاصيل.</p>
                                    @error('instructor_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2 md:col-span-2">
                                    <label class="block text-sm font-semibold text-ink">مسار البرنامج (التصفية في صفحة البرامج العامة)</label>
                                    <select name="course_category_id"
                                            class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                        <option value="">— بدون مسار —</option>
                                        @foreach($courseCategories as $cc)
                                            <option value="{{ $cc->id }}" {{ (string) old('course_category_id', $advancedCourse->course_category_id) === (string) $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted mt-1">
                                        إدارة القائمة من
                                        <a href="{{ route('admin.course-categories.index') }}" class="text-accent hover:underline font-semibold">مسارات البرامج</a>.
                                    </p>
                                    @error('course_category_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">مدة البرنامج (ساعات)</label>
                                    <input type="number" name="duration_hours" value="{{ old('duration_hours', $advancedCourse->duration_hours ?? 0) }}" min="0"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">مدة إضافية (دقائق)</label>
                                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $advancedCourse->duration_minutes ?? 0) }}" min="0" max="59"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">السعر بالريال السعودي (ر.س)</label>
                                    <input type="number" name="price_usd" value="{{ old('price_usd', $advancedCourse->price_usd ?? $advancedCourse->price_egp ?? $advancedCourse->price) }}" min="0" step="0.01"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                    @error('price_usd') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">بعد الخصم (ر.س)</label>
                                    <input type="number" name="price_usd_after_discount" value="{{ old('price_usd_after_discount', $advancedCourse->price_usd_after_discount ?? $advancedCourse->price_egp_after_discount ?? $advancedCourse->price_after_discount) }}" min="0" step="0.01"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                           placeholder="فارغ = بدون عرض ترويجي على البطاقة">
                                    <p class="text-xs text-muted">عملة المنصة: الريال السعودي.</p>
                                    @error('price_usd_after_discount') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2 md:col-span-2 rounded-xl border border-accent/20 bg-accent-soft/40 p-4">
                                    <label class="block text-sm font-semibold text-ink">نوع التعلّم * <span class="text-muted font-medium">(يظهر في /groups)</span></label>
                                    <select name="delivery_type" required class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink">
                                        <option value="group" @selected(old('delivery_type', $advancedCourse->delivery_type ?? 'group') === 'group')>جماعي — مجموعة منظّمة مع مدرّس</option>
                                        <option value="one_to_one" @selected(old('delivery_type', $advancedCourse->delivery_type ?? 'group') === 'one_to_one')>فردي 1:1 — جلسات خاصة مع المدرّس</option>
                                    </select>
                                    <p class="text-xs text-muted mt-2">أكمل التوصيف (الوصف، الأهداف، ماذا ستتعلّم، المتطلبات) ليظهر بالكامل في صفحة تفاصيل البرنامج العامة.</p>
                                    @error('delivery_type') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block text-sm font-semibold text-ink">نظام الدفع</label>
                                    <select name="billing_mode" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink">
                                        <option value="one_time" @selected(old('billing_mode', $advancedCourse->billing_mode ?? 'one_time') === 'one_time')>دفعة واحدة (وصول دائم)</option>
                                        <option value="monthly" @selected(old('billing_mode', $advancedCourse->billing_mode ?? 'one_time') === 'monthly')>اشتراك شهري متجدد</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">سعر الاشتراك الشهري (ر.س)</label>
                                    <input type="number" name="monthly_price" value="{{ old('monthly_price', $advancedCourse->monthly_price) }}" min="0" step="0.01"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink">
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">سعر شهري بعد الخصم (اختياري)</label>
                                    <input type="number" name="monthly_price_after_discount" value="{{ old('monthly_price_after_discount', $advancedCourse->monthly_price_after_discount) }}" min="0" step="0.01"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">وصف البرنامج</label>
                                <textarea name="description" rows="4"
                                          class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                          placeholder="اشرح محتوى البرنامج وقيمته للمتدربين.">{{ old('description', $advancedCourse->description) }}</textarea>
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-sm font-semibold text-ink">
                                    <i class="fas fa-video text-accent ml-1"></i>
                                    رابط الفيديو التقديمي (يظهر في صفحة البرنامج)
                                </label>
                                <input type="url" name="video_url" value="{{ old('video_url', $advancedCourse->video_url) }}"
                                       class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                       placeholder="رابط تضمين Bunny (iframe.mediadelivery.net)، YouTube، Vimeo، أو .mp4">
                                <p class="mt-1 text-xs text-muted">يُعرض في الصندوق الرئيسي بجانب وصف البرنامج.</p>
                                @error('video_url') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">أهداف البرنامج</label>
                                <textarea name="objectives" rows="3"
                                          class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                          placeholder="الأهداف التعليمية للبرنامج">{{ old('objectives', $advancedCourse->objectives) }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">تاريخ البداية</label>
                                    <input type="date" name="starts_at" value="{{ old('starts_at', $advancedCourse->starts_at ? $advancedCourse->starts_at->format('Y-m-d') : '') }}"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">تاريخ النهاية</label>
                                    <input type="date" name="ends_at" value="{{ old('ends_at', $advancedCourse->ends_at ? $advancedCourse->ends_at->format('Y-m-d') : '') }}"
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface shadow-soft">
                        <div class="border-b border-line px-5 py-4">
                            <h2 class="text-lg font-semibold text-ink">المهارات والمخرجات</h2>
                        </div>
                        <div class="p-6 sm:p-8 space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">المهارات المستهدفة (اختياري)</label>
                                @php
                                    $allSkills = \App\Models\AdvancedCourse::whereNotNull('skills')
                                        ->pluck('skills')
                                        ->flatMap(function($value) {
                                            if (is_array($value)) return $value;
                                            $decoded = is_string($value) ? json_decode($value, true) : null;
                                            return is_array($decoded) ? $decoded : [];
                                        })
                                        ->unique()->values();
                                @endphp
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach($allSkills as $skill)
                                        <button type="button" class="px-3 py-1 rounded-full text-xs font-semibold bg-[#f2f5f4] text-ink border border-line hover:border-accent transition"
                                                @click="addSkill('{{ $skill }}')">
                                            {{ $skill }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-2">
                                    <input id="customSkill" type="text" class="flex-1 rounded-xl border border-line bg-surface px-4 py-2 text-sm text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition" placeholder="اكتب مهارة جديدة">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-xl btn-press bg-accent hover:bg-[#0d4f4a] text-white px-4 py-2 text-sm font-semibold transition"
                                            @click="addSkill(document.getElementById('customSkill').value); document.getElementById('customSkill').value='';">
                                        <i class="fas fa-plus"></i>
                                        إضافة
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-3" x-show="selectedSkills.length">
                                    <template x-for="(skill, index) in selectedSkills" :key="skill">
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent-soft text-accent text-xs font-semibold">
                                            <span x-text="skill"></span>
                                            <button type="button" class="text-accent hover:text-[#0d4f4a]" @click="removeSkill(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <input type="hidden" name="skills[]" :value="skill">
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">المتطلبات المسبقة</label>
                                    <textarea name="prerequisites" rows="3"
                                              class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                              placeholder="ما الذي يجب أن يعرفه المتدرب قبل بدء البرنامج؟">{{ old('prerequisites', $advancedCourse->prerequisites) }}</textarea>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-ink">ما الذي سيتعلمه المتدرب؟</label>
                                    <textarea name="what_you_learn" rows="3"
                                              class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                              placeholder="المخرجات التعليمية والمهارات المكتسبة">{{ old('what_you_learn', $advancedCourse->what_you_learn) }}</textarea>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">متطلبات إضافية</label>
                                <textarea name="requirements" rows="3"
                                          class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition"
                                          placeholder="أدوات أو موارد يحتاجها المتدرب خلال الدراسة.">{{ old('requirements', $advancedCourse->requirements) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-line bg-surface shadow-soft">
                        <div class="border-b border-line px-5 py-4">
                            <h2 class="text-lg font-semibold text-ink">إعدادات العرض</h2>
                        </div>
                        <div class="p-6 sm:p-8 space-y-4 text-sm text-ink">
                            <label class="flex items-center justify-between">
                                <span class="font-medium">تفعيل البرنامج</span>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $advancedCourse->is_active) ? 'checked' : '' }} class="w-5 h-5 text-emerald-600 border-line rounded focus:ring-emerald-500">
                            </label>
                            <label class="flex items-center justify-between">
                                <span class="font-medium">وضع البرنامج ضمن المميزة</span>
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $advancedCourse->is_featured) ? 'checked' : '' }} class="w-5 h-5 text-amber-500 border-line rounded focus:ring-amber-500">
                            </label>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">لغة المحتوى</label>
                                <select name="language"
                                        class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                    <option value="ar" {{ old('language', $advancedCourse->language ?? 'ar') == 'ar' ? 'selected' : '' }}>العربية</option>
                                    <option value="en" {{ old('language', $advancedCourse->language) == 'en' ? 'selected' : '' }}>الإنجليزية</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-ink">صورة غلاف البرنامج</label>
                                @if($advancedCourse->thumbnail_url)
                                    <div class="mb-3 space-y-2">
                                        <img src="{{ $advancedCourse->thumbnail_url }}" alt="صورة البرنامج الحالية"
                                             class="w-full h-32 object-cover rounded-xl border border-line">
                                        <p class="text-xs text-muted">رابط العرض على الموقع (يُولَّد تلقائياً):</p>
                                        <input type="text" readonly value="{{ $advancedCourse->thumbnail_url }}" dir="ltr"
                                               class="w-full rounded-lg border border-line bg-[#f8faf9] px-3 py-2 text-xs text-ink">
                                        @if(!\App\Services\CourseThumbnailStorage::isExternalUrl($advancedCourse->thumbnail))
                                            <p class="text-xs text-muted">المسار المحفوظ: {{ $advancedCourse->thumbnail }}</p>
                                        @endif
                                    </div>
                                @endif
                                <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/jpg,image/webp"
                                       class="w-full rounded-xl border border-line bg-surface px-4 py-2 text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                <p class="text-xs text-muted">ارفع ملفاً جديداً لاستبدال الصورة الحالية.</p>
                                <div>
                                    <label class="block text-xs font-semibold text-muted mb-1">أو رابط صورة خارجي</label>
                                    <input type="url" name="thumbnail_link" value="{{ old('thumbnail_link', \App\Services\CourseThumbnailStorage::isExternalUrl($advancedCourse->thumbnail) ? $advancedCourse->thumbnail : '') }}" dir="ltr" placeholder="https://..."
                                           class="w-full rounded-xl border border-line bg-surface px-4 py-2 text-sm text-ink focus:border-accent focus:ring-2 focus:ring-accent/20 transition">
                                    @error('thumbnail_link') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                @if($advancedCourse->thumbnail)
                                    <label class="inline-flex items-center gap-2 text-sm text-rose-700 cursor-pointer">
                                        <input type="hidden" name="remove_thumbnail" value="0">
                                        <input type="checkbox" name="remove_thumbnail" value="1" class="rounded border-line text-rose-600">
                                        حذف الصورة الحالية
                                    </label>
                                @endif
                                @error('thumbnail') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface shadow-soft">
                        <div class="border-b border-line px-5 py-4">
                            <h2 class="text-lg font-semibold text-ink">ملخص</h2>
                        </div>
                        <div class="p-6 sm:p-8 space-y-3 text-sm text-muted">
                            <div class="flex items-center justify-between">
                                <span>عدد المهارات المحددة</span>
                                <span class="font-semibold text-ink" x-text="selectedSkills.length"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>الحالة</span>
                                <span class="font-semibold text-ink">{{ old('is_active', $advancedCourse->is_active) ? 'نشط' : 'مسودة' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface shadow-soft">
                        <div class="p-6 sm:p-8 space-y-3">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl btn-press bg-accent hover:bg-[#0d4f4a] text-white px-6 py-3 text-sm font-semibold transition">
                                <i class="fas fa-save"></i>
                                حفظ التعديلات
                            </button>
                            <a href="{{ route('admin.advanced-courses.show', $advancedCourse) }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl btn-press bg-accent hover:bg-[#0d4f4a] text-white px-6 py-3 text-sm font-semibold transition">
                                <i class="fas fa-eye"></i>
                                عرض البرنامج
                            </a>
                            <a href="{{ route('admin.advanced-courses.index') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-line px-6 py-3 text-sm font-semibold text-ink hover:bg-accent-soft hover:text-accent transition">
                                إلغاء
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function courseForm({ selectedSkills }) {
    return {
        selectedSkills: selectedSkills || [],
        addSkill(value) {
            const skill = (value || '').trim();
            if (!skill) return;
            if (!this.selectedSkills.includes(skill)) this.selectedSkills.push(skill);
        },
        removeSkill(index) {
            this.selectedSkills.splice(index, 1);
        }
    };
}
</script>
@endpush
@endsection
