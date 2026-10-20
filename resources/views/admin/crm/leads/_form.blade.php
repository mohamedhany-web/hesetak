{{-- نموذج إنشاء/تعديل عميل محتمل (أدمن CRM) --}}
@php
    $lead = $salesLead ?? null;
    $fieldClass = 'admin-select h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm leading-normal text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-8">
    <div class="space-y-5">
        <h3 class="border-b border-line pb-2 text-base font-semibold text-ink">بيانات العميل</h3>

        <div>
            <label class="{{ $labelClass }}" for="name">الاسم <span class="text-rose-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $lead?->name) }}" required class="{{ $fieldClass }}" placeholder="اسم العميل الكامل">
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}" for="email">البريد الإلكتروني</label>
                <input type="email" name="email" id="email" value="{{ old('email', $lead?->email) }}" class="{{ $fieldClass }}" placeholder="example@email.com">
                @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="phone">الهاتف</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $lead?->phone) }}" class="{{ $fieldClass }}" placeholder="01xxxxxxxxx">
                @error('phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="{{ $labelClass }}" for="company">الشركة / المؤسسة</label>
            <input type="text" name="company" id="company" value="{{ old('company', $lead?->company) }}" class="{{ $fieldClass }}" placeholder="اختياري">
            @error('company')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="space-y-5 border-t border-line pt-6">
        <h3 class="border-b border-line pb-2 text-base font-semibold text-ink">المصدر والاهتمام</h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}" for="source">المصدر <span class="text-rose-500">*</span></label>
                <select name="source" id="source" required class="{{ $fieldClass }}">
                    @foreach($sourceLabels as $val => $label)
                        <option value="{{ $val }}" @selected(old('source', $lead?->source ?? \App\Models\SalesLead::SOURCE_OTHER) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('source')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="interested_advanced_course_id">كورس الاهتمام</label>
                <select name="interested_advanced_course_id" id="interested_advanced_course_id" class="{{ $fieldClass }}">
                    <option value="">— اختياري —</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" @selected((string) old('interested_advanced_course_id', $lead?->interested_advanced_course_id) === (string) $c->id)>{{ $c->title }}</option>
                    @endforeach
                </select>
                @error('interested_advanced_course_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="{{ $labelClass }}" for="notes">ملاحظات</label>
            <textarea name="notes" id="notes" rows="4" class="{{ $areaClass }}" placeholder="تفاصيل أول تواصل، الاهتمام، المواعيد...">{{ old('notes', $lead?->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="space-y-5 border-t border-line pt-6">
        <h3 class="border-b border-line pb-2 text-base font-semibold text-ink">التعيين (اختياري)</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @if(! $lead)
            <div>
                <label class="{{ $labelClass }}" for="marketing_owner_id">مالك التسويق</label>
                <select name="marketing_owner_id" id="marketing_owner_id" class="{{ $fieldClass }}">
                    <option value="">أنا (الحساب الحالي)</option>
                    @foreach($marketingUsers as $u)
                        <option value="{{ $u->id }}" @selected((string) old('marketing_owner_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                @error('marketing_owner_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif
            <div>
                <label class="{{ $labelClass }}" for="assigned_to">موظف المبيعات</label>
                <select name="assigned_to" id="assigned_to" class="{{ $fieldClass }}">
                    <option value="">— بدون تعيين —</option>
                    @foreach($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected((string) old('assigned_to', $lead?->assigned_to) === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="crm_group_id">مجموعة الفريق</label>
                <select name="crm_group_id" id="crm_group_id" class="{{ $fieldClass }}">
                    <option value="">— اختياري —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" @selected((string) old('crm_group_id', $lead?->crm_group_id) === (string) $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
                @error('crm_group_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        @if($lead)
            <p class="text-xs text-muted">مالك التسويق ثابت ولا يُغيَّر بعد الإنشاء ({{ $lead->marketingOwner?->name ?? '—' }}).</p>
        @endif
    </div>
</div>
