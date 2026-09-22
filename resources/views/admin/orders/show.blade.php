@extends('layouts.admin')

@section('title', 'تفاصيل الطلب #' . $order->id . ' - ' . config('app.name'))
@section('page_title', 'تفاصيل الطلب #' . $order->id)

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $statusTone = match ($order->status) {
        'pending' => ['badge' => 'bg-metal/15 text-metal', 'icon' => 'fa-clock', 'hint' => 'جاري المراجعة'],
        'approved' => ['badge' => 'bg-accent-soft text-accent', 'icon' => 'fa-check-circle', 'hint' => 'تمت الموافقة'],
        default => ['badge' => 'bg-canvas-muted text-muted', 'icon' => 'fa-times-circle', 'hint' => 'تم الرفض'],
    };
@endphp

<div class="space-y-5">

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · الطلبات · #{{ $order->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تفاصيل الطلب #{{ $order->id }}</h2>
            <p class="mt-1 text-sm text-muted">
                <i class="fas fa-calendar-alt text-xs"></i>
                {{ $order->created_at->format('d/m/Y - H:i') }}
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للطلبات
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- تفاصيل الطلب --}}
        <div class="space-y-5 xl:col-span-2">
            {{-- معلومات العميل --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">معلومات العميل</h3>
                    <p class="mt-0.5 text-xs text-muted">بيانات المتعلم المرتبط بالطلب</p>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">الاسم</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ htmlspecialchars($order->user->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">رقم الهاتف</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ htmlspecialchars($order->user->phone ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">البريد الإلكتروني</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-ink">{{ htmlspecialchars($order->user->email ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">تاريخ التسجيل</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ $order->user->created_at ? $order->user->created_at->format('d/m/Y') : 'غير محدد' }}</dd>
                    </div>
                </dl>
            </article>

            {{-- مبيعات ومتابعة المندوب --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">المبيعات والمتابعة</h3>
                    <p class="mt-0.5 text-xs text-muted">تعيين مندوب مبيعات وملاحظات الفريق (تظهر لموظف السيلز أيضاً).</p>
                </div>
                <div class="space-y-5 p-4 sm:p-5">
                    @if(session('success'))
                        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ session('error') }}</div>
                    @endif
                    <form action="{{ route('admin.orders.sales-assign', $order) }}" method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <label class="{{ $labelClass }}">مندوب المبيعات</label>
                            <select name="sales_owner_id" class="{{ $fieldClass }}">
                                <option value="">— بدون مندوب —</option>
                                @foreach($salesEmployees ?? [] as $se)
                                    <option value="{{ $se->id }}" {{ (int) old('sales_owner_id', $order->sales_owner_id) === (int) $se->id ? 'selected' : '' }}>{{ $se->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                            حفظ
                        </button>
                    </form>
                    @if($order->sales_contacted_at)
                        <p class="text-xs text-muted">آخر نشاط مبيعات: {{ $order->sales_contacted_at->format('d/m/Y H:i') }}</p>
                    @endif
                    <div>
                        <h4 class="mb-3 text-sm font-semibold text-ink">سجل الملاحظات</h4>
                        <div class="mb-4 max-h-56 space-y-3 overflow-y-auto">
                            @forelse($order->salesNotes ?? [] as $note)
                                <div class="rounded-xl border border-line bg-canvas px-4 py-3 text-sm">
                                    <p class="whitespace-pre-wrap text-ink">{{ $note->body }}</p>
                                    <p class="mt-2 text-xs text-muted">{{ $note->user?->name }} — {{ $note->created_at->format('Y-m-d H:i') }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-muted">لا توجد ملاحظات بعد.</p>
                            @endforelse
                        </div>
                        <form action="{{ route('admin.orders.sales-notes.store', $order) }}" method="POST" class="space-y-3">
                            @csrf
                            <textarea name="body" rows="2" required maxlength="5000" class="{{ $areaClass }}" placeholder="ملاحظة للفريق…"></textarea>
                            <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink transition hover:border-accent/30 hover:text-accent">
                                إضافة ملاحظة
                            </button>
                        </form>
                    </div>
                </div>
            </article>

            {{-- معلومات الكورس أو المسار التعليمي --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">
                        {{ in_array($order->order_type, [\App\Models\Order::TYPE_SERVICE_PACKAGE, \App\Models\Order::TYPE_CUSTOM_SERVICE_PACKAGE], true) ? 'معلومات باقة الحصص' : ($order->academic_year_id && ! $order->advanced_course_id ? 'طلب قديم' : 'معلومات الكورس') }}
                    </h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex h-24 w-full flex-shrink-0 items-center justify-center rounded-xl bg-accent-soft sm:w-24">
                            @if($order->course && $order->course->thumbnail)
                                <img src="{{ storage_asset($order->course->thumbnail) }}" alt="{{ htmlspecialchars($order->course->title ?? 'كورس', ENT_QUOTES, 'UTF-8') }}"
                                     class="h-full w-full rounded-xl object-cover" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%230B3D91\' d=\'M8 5v14l11-7z\'/%3E%3C/svg%3E';">
                            @elseif(in_array($order->order_type, [\App\Models\Order::TYPE_SERVICE_PACKAGE, \App\Models\Order::TYPE_CUSTOM_SERVICE_PACKAGE], true))
                                <i class="fas fa-box-open text-2xl text-accent"></i>
                            @else
                                <i class="fas fa-play-circle text-2xl text-accent"></i>
                            @endif
                        </div>

                        <div class="flex-1">
                            @if($order->order_type === \App\Models\Order::TYPE_CUSTOM_SERVICE_PACKAGE)
                                @php $custom = $order->custom_package_data ?? []; @endphp
                                <h4 class="mb-3 text-base font-semibold text-ink">{{ $custom['name'] ?? 'باقة مخصصة' }}</h4>
                                <div class="grid gap-2 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">الحصص</span><strong>{{ $custom['sessions'] ?? '—' }}</strong></div>
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">مدة الحصة</span><strong>{{ $custom['session_minutes'] ?? '—' }} دقيقة</strong></div>
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">الصلاحية</span><strong>{{ $custom['duration_days'] ?? '—' }} يوم</strong></div>
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">سعر الحصة النهائي</span><strong>{{ number_format((float) ($custom['final_price_per_session'] ?? 0), 2) }} {{ currency_symbol() }}</strong></div>
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">خصم الكمية</span><strong>{{ $custom['discount_percent'] ?? 0 }}%</strong></div>
                                    <div class="rounded-xl bg-canvas p-3"><span class="block text-xs text-muted">النطاق</span><strong>{{ $custom['scope_label'] ?? '—' }}</strong></div>
                                </div>
                            @elseif($order->servicePackage)
                                <h4 class="mb-2 text-base font-semibold text-ink">{{ $order->servicePackage->name }}</h4>
                                <p class="text-sm text-muted">{{ $order->servicePackage->units_count }} حصة × {{ $order->servicePackage->sessionMinutes() }} دقيقة · {{ $order->servicePackage->validityLabel() }}</p>
                            @elseif($order->academic_year_id && ! $order->advanced_course_id)
                                <h4 class="mb-2 text-base font-semibold text-ink">{{ htmlspecialchars($order->learningPath->name ?? 'طلب قديم', ENT_QUOTES, 'UTF-8') }}</h4>
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-ink-soft">
                                        <i class="fas fa-clock text-xs"></i>
                                        طلب قديم
                                    </span>
                                    @if($order->amount)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                                        <i class="fas fa-money-bill-wave text-xs"></i>
                                        {{ number_format($order->amount, 2) }} {{ $order->currencyCode() }}
                                    </span>
                                    @endif
                                </div>
                            @elseif($order->course)
                                <h4 class="mb-2 text-base font-semibold text-ink">{{ htmlspecialchars($order->course->title ?? 'كورس غير محدد', ENT_QUOTES, 'UTF-8') }}</h4>
                                @if($order->course->academicYear || $order->course->academicSubject)
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    @if($order->course->academicYear)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                                        <i class="fas fa-graduation-cap text-xs"></i>
                                        {{ htmlspecialchars($order->course->academicYear->name, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                    @endif
                                    @if($order->course->academicSubject)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-ink-soft">
                                        <i class="fas fa-layer-group text-xs"></i>
                                        {{ htmlspecialchars($order->course->academicSubject->name, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                    @endif
                                </div>
                                @endif
                                @if($order->course->description)
                                    <p class="text-sm text-muted">
                                        {{ htmlspecialchars(Str::limit($order->course->description, 150), ENT_QUOTES, 'UTF-8') }}
                                    </p>
                                @endif
                            @else
                                <h4 class="mb-2 text-base font-semibold text-ink">غير محدد</h4>
                                <p class="text-sm text-muted">لا توجد معلومات متاحة</p>
                            @endif
                        </div>
                    </div>
                </div>
            </article>

            @if($order->isTutoringOrder())
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">التنفيذ والتسكين</h3>
                        <p class="mt-1 text-xs text-muted">الرصيد والحجوزات الناتجة من هذا الطلب</p>
                    </div>
                    @if($order->serviceEntitlements->isNotEmpty())
                        @php
                            $firstEnt = $order->serviceEntitlements->first();
                            $placementMode = in_array($firstEnt->scope, [
                                \App\Models\ServicePackage::SCOPE_PRIVATE_LESSONS,
                                \App\Models\ServicePackage::SCOPE_GLOBAL,
                            ], true) ? 'private' : 'group';
                        @endphp
                        <a href="{{ route('admin.placement.create', ['entitlement_id' => $firstEnt->id, 'student_id' => $order->user_id, 'mode' => $placementMode]) }}" class="inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                            <i class="fas fa-user-plus"></i> تسكين حصة
                        </a>
                    @endif
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    @forelse($order->serviceEntitlements as $entitlement)
                        <div class="rounded-xl border border-line bg-canvas/50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-ink">رصيد #{{ $entitlement->id }} — {{ \App\Models\ServicePackage::scopes()[$entitlement->scope] ?? $entitlement->scope }}</p>
                                    <p class="mt-1 text-xs text-muted">{{ $entitlement->tutoringGroup?->title ?: 'غير مقيد بمجموعة' }} · ينتهي {{ $entitlement->expires_at?->format('Y-m-d') ?: 'بدون تاريخ' }}</p>
                                </div>
                                <div class="text-end">
                                    <p class="font-semibold text-accent">{{ $entitlement->unitsLeft() }} / {{ $entitlement->units_total }} متبقٍ</p>
                                    <p class="text-xs text-muted">{{ \App\Services\StudentEntitlementService::bookableUnitsLeft($entitlement) }} قابل للحجز</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-line p-4 text-sm text-muted">
                            {{ $order->status === \App\Models\Order::STATUS_APPROVED ? 'لم يُعثر على رصيد مرتبط — راجع سجل التفعيل.' : 'سيُنشأ الرصيد تلقائياً بعد الموافقة.' }}
                        </div>
                    @endforelse

                    @if($order->status === \App\Models\Order::STATUS_APPROVED)
                        <form method="POST" action="{{ route('admin.orders.refulfill-tutoring', $order) }}" class="pt-1">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                                <i class="fas fa-rotate"></i> إعادة تفعيل رصيد الباقة
                            </button>
                            @if(str_contains((string) $order->notes, '[TUTORING_FULFILL_FAILED]'))
                                <p class="mt-2 text-xs font-medium text-rose-600">فشل تفعيل سابق بعد الدفع — استخدم الزر أعلاه.</p>
                            @endif
                        </form>
                    @endif

                    @if($order->tutoringGroupBookings->isNotEmpty())
                        <div>
                            <h4 class="mb-2 text-sm font-semibold text-ink">الحجوزات المرتبطة</h4>
                            <div class="space-y-2">
                                @foreach($order->tutoringGroupBookings as $booking)
                                    @if(Route::has('admin.tutoring-group-bookings.show'))
                                    <a href="{{ route('admin.tutoring-group-bookings.show', $booking) }}" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line px-4 py-3 text-sm hover:border-accent/30">
                                        <span class="font-medium text-ink">{{ $booking->tutoringGroup?->title ?: 'حجز #'.$booking->id }}</span>
                                        <span class="text-muted">{{ $booking->instructor?->name ?: 'بدون معلم' }} · {{ $booking->starts_at?->format('Y-m-d H:i') }} · {{ $booking->statusLabel() }}</span>
                                    </a>
                                    @else
                                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line px-4 py-3 text-sm">
                                        <span class="font-medium text-ink">{{ $booking->tutoringGroup?->title ?: 'حجز #'.$booking->id }}</span>
                                        <span class="text-muted">{{ $booking->instructor?->name ?: 'بدون معلم' }} · {{ $booking->starts_at?->format('Y-m-d H:i') }} · {{ $booking->statusLabel() }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </article>
            @endif

            {{-- تفاصيل الدفع --}}
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">تفاصيل الدفع</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium text-muted">المبلغ</dt>
                            <dd class="mt-1 text-xl font-semibold tabular-nums text-accent">
                                {{ number_format($order->amount, 2) }} <span class="text-sm font-medium text-muted">{{ $order->currencyCode() }}</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium text-muted">طريقة الدفع</dt>
                            <dd class="mt-1">
                                @if($order->payment_method == 'bank_transfer')
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                                        <i class="fas fa-university text-xs"></i>
                                        تحويل بنكي
                                    </span>
                                @elseif($order->payment_method == 'cash')
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-ink-soft">
                                        <i class="fas fa-money-bill text-xs"></i>
                                        نقدي
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas px-2.5 py-1 text-xs font-medium text-muted">
                                        <i class="fas fa-question-circle text-xs"></i>
                                        أخرى
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium text-muted">تاريخ الطلب</dt>
                            <dd class="mt-1 text-sm font-medium text-ink">
                                {{ $order->created_at->format('d/m/Y') }}
                                <span class="font-normal text-muted">- {{ $order->created_at->format('H:i') }}</span>
                            </dd>
                        </div>

                        @if($order->approved_at)
                        <div>
                            <dt class="text-xs font-medium text-muted">تاريخ المراجعة</dt>
                            <dd class="mt-1 text-sm font-medium text-ink">
                                {{ $order->approved_at->format('d/m/Y') }}
                                <span class="font-normal text-muted">- {{ $order->approved_at->format('H:i') }}</span>
                            </dd>
                        </div>
                        @endif
                    </dl>

                    @if($order->wallet)
                    <div class="mt-4 rounded-xl border border-line bg-canvas px-4 py-3">
                        <p class="mb-1.5 text-xs font-medium text-muted">حساب الاستلام على المنصة</p>
                        <div class="text-sm font-semibold text-ink">
                            {{ $order->wallet->name ?? \App\Models\Wallet::typeLabel($order->wallet->type) }}
                            @if($order->wallet->account_number)
                                <span class="font-mono font-medium text-ink-soft"> — {{ $order->wallet->account_number }}</span>
                            @endif
                        </div>
                    </div>
                    @elseif(in_array($order->payment_method, ['bank_transfer', 'wallet'], true))
                    <div class="mt-4 rounded-xl border border-line bg-canvas px-4 py-3">
                        <p class="flex items-center gap-2 text-sm text-ink-soft">
                            <i class="fas fa-exclamation-triangle text-metal"></i>
                            لم يُحدَّد حساب استلام على المنصة لهذا الطلب؛ ولن يُسجَّل رصيد على المحفظة عند الموافقة حتى يتم التحديد.
                        </p>
                    </div>
                    @endif

                    @if($order->status === \App\Models\Order::STATUS_PENDING && in_array($order->payment_method, ['bank_transfer', 'wallet'], true) && isset($platformWallets) && $platformWallets->count() > 0)
                    <div class="mt-4 rounded-xl border border-line bg-canvas p-4">
                        <h4 class="mb-1 text-sm font-semibold text-ink">حساب التحويل على المنصة (للإيداع عند الموافقة)</h4>
                        <p class="mb-4 text-xs text-muted">اختر المحفظة التي استلمتم عليها التحويل. عند الموافقة يُضاف المبلغ تلقائياً لرصيدها مع قيد في معاملات المحفظة.</p>
                        <form action="{{ route('admin.orders.receiving-wallet', $order) }}" method="post" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            @method('PATCH')
                            <div class="flex-1">
                                <label for="receiving_wallet_id" class="{{ $labelClass }}">الحساب</label>
                                <select name="wallet_id" id="receiving_wallet_id" required class="{{ $fieldClass }}">
                                    <option value="">— اختر —</option>
                                    @foreach($platformWallets as $w)
                                        <option value="{{ $w->id }}" @selected((string) old('wallet_id', $order->wallet_id) === (string) $w->id)>
                                            {{ $w->name ?? \App\Models\Wallet::typeLabel($w->type) }}@if($w->account_number) — {{ $w->account_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                                <i class="fas fa-save text-xs"></i>
                                حفظ
                            </button>
                        </form>
                    </div>
                    @endif

                    @if($order->notes)
                        <div class="mt-4 border-t border-line pt-4">
                            <p class="text-xs font-medium text-muted">ملاحظات العميل</p>
                            <div class="mt-2 whitespace-pre-wrap rounded-xl bg-canvas px-4 py-3 text-sm text-ink">
                                {{ htmlspecialchars($order->notes, ENT_QUOTES, 'UTF-8') }}
                            </div>
                        </div>
                    @endif
                </div>
            </article>

            {{-- صورة الإيصال --}}
            @if($order->payment_proof)
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">إيصال الدفع</h3>
                        <p class="mt-0.5 text-xs text-muted">اضغط على الصورة لعرضها بحجم أكبر</p>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="text-center">
                            <div class="inline-block rounded-xl border border-line bg-canvas p-3">
                                @php
                                    $fullPath = storage_path('app/public/' . $order->payment_proof);
                                    $imageExists = file_exists($fullPath);

                                    $imageUrl = null;
                                    if ($imageExists) {
                                        $imageUrl = storage_asset($order->payment_proof);
                                        if (!file_exists(public_path('storage/' . $order->payment_proof))) {
                                            try {
                                                $imageUrl = route('storage.file', ['path' => htmlspecialchars($order->payment_proof, ENT_QUOTES, 'UTF-8')]);
                                            } catch (\Exception $e) {
                                                $imageUrl = url('/storage/' . htmlspecialchars($order->payment_proof, ENT_QUOTES, 'UTF-8'));
                                            }
                                        }
                                    }
                                @endphp
                                @if($imageExists)
                                <img src="{{ htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') }}"
                                     alt="إيصال الدفع"
                                     class="h-auto max-w-full cursor-pointer rounded-xl transition hover:opacity-90"
                                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';"
                                     onclick="openImageModal(this.src)">
                                <div class="hidden rounded-xl border border-line bg-canvas px-4 py-3">
                                    <p class="flex items-center gap-2 text-sm text-ink-soft">
                                        <i class="fas fa-exclamation-triangle text-metal"></i>
                                        <span>الصورة غير متوفرة حالياً</span>
                                    </p>
                                </div>
                                @else
                                <div class="rounded-xl border border-line bg-canvas px-4 py-3">
                                    <p class="flex items-center gap-2 text-sm text-ink-soft">
                                        <i class="fas fa-exclamation-triangle text-metal"></i>
                                        <span>الصورة غير موجودة في الخادم</span>
                                    </p>
                                </div>
                                @endif
                            </div>
                            <p class="mt-4 flex items-center justify-center gap-2 text-xs text-muted">
                                <i class="fas fa-info-circle"></i>
                                اضغط على الصورة لعرضها بحجم أكبر
                            </p>
                        </div>
                    </div>
                </article>
            @endif
        </div>

        {{-- حالة الطلب والإجراءات --}}
        <div class="space-y-5">
            <article class="sticky top-8 overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">حالة الطلب</h3>
                        <p class="mt-0.5 text-xs text-muted">الموافقة أو الرفض</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $statusTone['badge'] }}">
                        <i class="fas {{ $statusTone['icon'] }} text-xs"></i>
                        {{ htmlspecialchars($order->status_text ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}
                    </span>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="mb-6 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-xl {{ $statusTone['badge'] }}">
                            <i class="fas {{ $statusTone['icon'] }} text-2xl"></i>
                        </div>

                        <div class="mb-1 text-lg font-semibold text-ink">
                            {{ htmlspecialchars($order->status_text ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}
                        </div>
                        <p class="text-sm text-muted">{{ $statusTone['hint'] }}</p>
                    </div>

                    @if($order->status == 'pending')
                        @if($order->payment_method === 'online')
                            <form method="POST" action="{{ route('admin.orders.reconcile-fawaterak', $order) }}" class="mb-4 space-y-2 rounded-xl border border-sky-200 bg-sky-50/60 p-4">
                                @csrf
                                <p class="text-sm font-semibold text-ink">تأكيد دفع فواتيرك</p>
                                <p class="text-xs text-muted">إذا تم خصم المبلغ ولم تُنشأ فاتورة أو تُفعَّل الباقة، أدخل رقم فاتورة فواتيرك ثم اضغط تأكيد.</p>
                                <input type="text" name="fawaterak_invoice_id" value="{{ old('fawaterak_invoice_id', $order->fawaterak_invoice_id) }}" dir="ltr" placeholder="رقم فاتورة فواتيرك"
                                       class="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                                <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white hover:bg-sky-500">
                                    <i class="fas fa-sync-alt ml-2"></i> تأكيد الدفع من فواتيرك
                                </button>
                            </form>
                        @endif
                        <script>
                            // تعريف الدوال مباشرة قبل الأزرار لضمان توفرها
                            (function() {
                                let isProcessing = false;

                                window.approveOrder = async function(orderId) {
                                    console.log('approveOrder called with orderId:', orderId);

                                    if (isProcessing) {
                                        console.log('Already processing, returning...');
                                        return;
                                    }

                                    const confirmed = confirm('هل أنت متأكد من الموافقة على هذا الطلب؟\nسيتم تفعيل الخدمة أو الرصيد المرتبط تلقائياً.');
                                    if (!confirmed) {
                                        console.log('User cancelled approval');
                                        return;
                                    }

                                    console.log('Starting approval process...');

                                    const approveBtn = document.getElementById('approveBtn');
                                    const rejectBtn = document.getElementById('rejectBtn');
                                    const originalApproveText = approveBtn ? approveBtn.innerHTML : '';

                                    isProcessing = true;
                                    if (approveBtn) {
                                        approveBtn.disabled = true;
                                        approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري المعالجة...';
                                    }
                                    if (rejectBtn) rejectBtn.disabled = true;

                                    try {
                                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                                        if (!csrfToken) {
                                            alert('خطأ: لم يتم العثور على CSRF token');
                                            if (approveBtn) {
                                                approveBtn.disabled = false;
                                                approveBtn.innerHTML = originalApproveText;
                                            }
                                            if (rejectBtn) rejectBtn.disabled = false;
                                            isProcessing = false;
                                            return;
                                        }

                                        console.log('CSRF Token found, making request...');

                                        const formData = new FormData();
                                        formData.append('_token', csrfToken);

                                        const controller = new AbortController();
                                        const timeoutId = setTimeout(() => controller.abort(), 120000);

                                        console.log('Fetching:', `/admin/orders/${orderId}/approve`);

                                        const response = await fetch(`/admin/orders/${orderId}/approve`, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': csrfToken,
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            },
                                            body: formData,
                                            signal: controller.signal
                                        });

                                        clearTimeout(timeoutId);

                                        const contentType = response.headers.get('content-type');
                                        let data;

                                        if (contentType && contentType.includes('application/json')) {
                                            data = await response.json();
                                        } else {
                                            if (response.redirected || response.status === 200) {
                                                window.location.reload();
                                                return;
                                            }
                                            const text = await response.text();
                                            throw new Error(text || 'حدث خطأ أثناء المعالجة');
                                        }

                                        if (response.ok) {
                                            if (data.redirect) {
                                                window.location.href = data.redirect;
                                            } else {
                                                window.location.reload();
                                            }
                                        } else {
                                            console.error('Error Response Data:', data);
                                            const errorMsg = data.error || data.message || 'حدث خطأ أثناء المعالجة';
                                            const errorDetails = data.file && data.line ? `\n\nالملف: ${data.file}\nالسطر: ${data.line}` : '';
                                            alert(errorMsg + errorDetails);

                                            if (approveBtn) {
                                                approveBtn.disabled = false;
                                                approveBtn.innerHTML = originalApproveText;
                                            }
                                            if (rejectBtn) rejectBtn.disabled = false;
                                            isProcessing = false;
                                        }
                                    } catch (error) {
                                        console.error('Error in approveOrder:', error);
                                        let errorMessage = '';

                                        if (error.name === 'AbortError') {
                                            errorMessage = 'انتهت مهلة الانتظار. العملية تستغرق وقتاً طويلاً. يرجى المحاولة مرة أخرى أو مراجعة السجلات.';
                                        } else if (error.message) {
                                            errorMessage = error.message;
                                        } else {
                                            errorMessage = 'حدث خطأ غير معروف أثناء الموافقة على الطلب: ' + error.toString();
                                        }

                                        alert(errorMessage);

                                        if (approveBtn) {
                                            approveBtn.disabled = false;
                                            approveBtn.innerHTML = originalApproveText;
                                        }
                                        if (rejectBtn) rejectBtn.disabled = false;
                                        isProcessing = false;
                                    }
                                };

                                window.rejectOrder = async function(orderId) {
                                    if (isProcessing) return;

                                    const confirmed = confirm('هل أنت متأكد من رفض هذا الطلب؟');
                                    if (!confirmed) return;

                                    const approveBtn = document.getElementById('approveBtn');
                                    const rejectBtn = document.getElementById('rejectBtn');
                                    const originalRejectText = rejectBtn ? rejectBtn.innerHTML : '';

                                    isProcessing = true;
                                    if (approveBtn) approveBtn.disabled = true;
                                    if (rejectBtn) {
                                        rejectBtn.disabled = true;
                                        rejectBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري المعالجة...';
                                    }

                                    try {
                                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                                        const formData = new FormData();
                                        formData.append('_token', csrfToken);

                                        const controller = new AbortController();
                                        const timeoutId = setTimeout(() => controller.abort(), 60000);

                                        const response = await fetch(`/admin/orders/${orderId}/reject`, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': csrfToken,
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            },
                                            body: formData,
                                            signal: controller.signal
                                        });

                                        clearTimeout(timeoutId);

                                        const contentType = response.headers.get('content-type');
                                        let data;

                                        if (contentType && contentType.includes('application/json')) {
                                            data = await response.json();
                                        } else {
                                            if (response.redirected || response.status === 200) {
                                                window.location.reload();
                                                return;
                                            }
                                            const text = await response.text();
                                            throw new Error(text || 'حدث خطأ أثناء المعالجة');
                                        }

                                        if (response.ok) {
                                            if (data.redirect) {
                                                window.location.href = data.redirect;
                                            } else {
                                                window.location.reload();
                                            }
                                        } else {
                                            console.error('Error Response Data:', data);
                                            const errorMsg = data.error || data.message || 'حدث خطأ أثناء المعالجة';
                                            const errorDetails = data.file && data.line ? `\n\nالملف: ${data.file}\nالسطر: ${data.line}` : '';
                                            alert(errorMsg + errorDetails);

                                            if (approveBtn) approveBtn.disabled = false;
                                            if (rejectBtn) {
                                                rejectBtn.disabled = false;
                                                rejectBtn.innerHTML = originalRejectText;
                                            }
                                            isProcessing = false;
                                        }
                                    } catch (error) {
                                        console.error('Error:', error);
                                        let errorMessage = '';

                                        if (error.name === 'AbortError') {
                                            errorMessage = 'انتهت مهلة الانتظار. يرجى المحاولة مرة أخرى.';
                                        } else if (error.message) {
                                            errorMessage = error.message;
                                        } else {
                                            errorMessage = 'حدث خطأ غير معروف أثناء رفض الطلب';
                                        }

                                        alert(errorMessage);

                                        if (approveBtn) approveBtn.disabled = false;
                                        if (rejectBtn) {
                                            rejectBtn.disabled = false;
                                            rejectBtn.innerHTML = originalRejectText;
                                        }
                                        isProcessing = false;
                                    }
                                };

                                console.log('Order approval functions ready:', typeof window.approveOrder, typeof window.rejectOrder);
                            })();
                        </script>
                        <div class="space-y-3">
                            <button type="button"
                                    id="approveBtn"
                                    onclick="window.approveOrder({{ $order->id }}); return false;"
                                    class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">
                                <i class="fas fa-check text-xs"></i>
                                الموافقة على الطلب
                            </button>

                            <button type="button"
                                    id="rejectBtn"
                                    onclick="window.rejectOrder({{ $order->id }}); return false;"
                                    class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-rose-600 transition hover:border-rose-300 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50">
                                <i class="fas fa-times text-xs"></i>
                                رفض الطلب
                            </button>
                        </div>
                    @elseif($order->status == 'approved')
                        <div class="rounded-xl border border-line bg-canvas px-4 py-3">
                            <p class="flex items-start gap-2 text-sm text-ink">
                                <i class="fas fa-check-circle mt-0.5 text-accent"></i>
                                <span>تمت الموافقة على الطلب وتفعيل الخدمة أو الرصيد المرتبط للعميل.</span>
                            </p>
                        </div>
                    @else
                        <div class="rounded-xl border border-line bg-canvas px-4 py-3">
                            <p class="flex items-start gap-2 text-sm text-ink-soft">
                                <i class="fas fa-exclamation-circle mt-0.5 text-muted"></i>
                                <span>تم رفض هذا الطلب.</span>
                            </p>
                        </div>
                    @endif

                    @if($order->approver)
                        <div class="mt-6 border-t border-line pt-5">
                            <p class="mb-3 text-xs font-medium text-muted">تمت المراجعة بواسطة:</p>
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">
                                    {{ mb_substr(htmlspecialchars($order->approver->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8'), 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-ink">{{ htmlspecialchars($order->approver->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</p>
                                    @if($order->approved_at)
                                        <p class="text-xs text-muted">{{ $order->approved_at->format('d/m/Y - H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </div>
</div>

{{-- Modal لعرض الصورة --}}
<div id="imageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 backdrop-blur-sm" onclick="closeImageModal()">
    <div class="relative max-h-[90vh] max-w-5xl p-4">
        <button onclick="closeImageModal()" class="absolute -top-12 left-0 text-3xl font-bold text-white transition hover:text-white/70">
            <i class="fas fa-times-circle"></i>
        </button>
        <img id="modalImage" src="" alt="إيصال الدفع" class="max-h-[90vh] max-w-full rounded-xl object-contain shadow-2xl" onerror="closeImageModal();">
    </div>
</div>

@push('scripts')
<script>
    // الكود الرئيسي موجود في inline script قبل الأزرار
    // هذا القسم فقط للدوال المساعدة الأخرى

    function openImageModal(src) {
        // حماية من XSS - التحقق من URL
        if (!src || !src.startsWith('http') && !src.startsWith('/')) {
            return;
        }
        const img = document.getElementById('modalImage');
        if (img) {
            img.src = src;
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            const img = document.getElementById('modalImage');
            if (img) {
                img.src = '';
            }
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });

</script>
@endpush
@endsection
