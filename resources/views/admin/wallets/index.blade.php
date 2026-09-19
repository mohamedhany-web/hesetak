@extends('layouts.admin')

@section('title', 'المحافظ الذكية')
@section('page_title', 'المحافظ الذكية')

@section('content')
@php
    $recentWallet = ($recentWallets ?? collect())->first();
    $netMonth = ($currentMonthDeposits ?? 0) - ($currentMonthWithdrawals ?? 0);
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $kpis = [
        ['label' => 'إجمالي المحافظ', 'value' => $stats['total'] ?? 0, 'icon' => 'fa-wallet', 'tone' => 'accent', 'note' => 'يشمل كل المحافظ المربوطة بالطلاب'],
        ['label' => 'الرصيد المتاح', 'value' => number_format($stats['total_balance'] ?? 0, 2), 'icon' => 'fa-coins', 'tone' => 'accent', 'note' => 'إجمالي الأرصدة الحالية بكل المحافظ', 'suffix' => ' ' . currency_symbol()],
        ['label' => 'الرصيد المعلّق', 'value' => number_format($stats['pending_balance'] ?? 0, 2), 'icon' => 'fa-hourglass-half', 'tone' => 'metal', 'note' => 'المبالغ المعلّقة أو قيد المراجعة', 'suffix' => ' ' . currency_symbol()],
        ['label' => 'صافي تدفقات الشهر', 'value' => number_format($netMonth, 2), 'icon' => 'fa-wave-square', 'tone' => 'muted', 'note' => 'الإيداعات ناقص السحوبات خلال ' . \Carbon\Carbon::now()->translatedFormat('F'), 'suffix' => ' ' . currency_symbol()],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · المحافظ</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">المحافظ الذكية</h2>
            <p class="mt-1 text-sm text-muted">إدارة محافظ الدفع المربوطة بالطلاب مع متابعة الأرصدة، المعاملات، وأنواع القنوات المالية المختلفة.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if($recentWallet)
                <a href="{{ route('admin.wallets.reports', $recentWallet) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-chart-pie text-xs"></i>
                    تقارير سريعة
                </a>
            @endif
            <a href="{{ route('admin.wallets.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إضافة محفظة جديدة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
            <span class="size-1.5 rounded-full bg-accent"></span>
            نشطة: {{ htmlspecialchars($stats['active'] ?? 0) }}
        </span>
        <span class="inline-flex items-center gap-2 rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">
            <span class="size-1.5 rounded-full bg-muted"></span>
            غير نشطة: {{ htmlspecialchars($stats['inactive'] ?? 0) }}
        </span>
        <span class="inline-flex items-center gap-2 rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">
            <i class="fas fa-chart-line text-[10px]"></i>
            المعاملات المسجلة: {{ number_format($totalTransactions ?? 0) }}
        </span>
    </div>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ $kpi['value'] }}{{ $kpi['suffix'] ?? '' }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">تحويل بين المحافظ</h3>
                <p class="mt-0.5 text-xs text-muted">يمكنك تحويل رصيد من محفظة إلى أخرى من محافظك الشخصية فقط.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">
                <i class="fas fa-exchange-alt text-[10px]"></i>
                {{ ($transferWallets ?? collect())->count() }} محافظ متاحة
            </span>
        </div>

        @if(($transferWallets ?? collect())->count() >= 2)
            <form method="POST" action="{{ route('admin.wallets.transfer') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                @csrf
                <div>
                    <label class="{{ $labelClass }}" for="from_wallet_id">من محفظة</label>
                    <select id="from_wallet_id" name="from_wallet_id" class="{{ $fieldClass }}" required>
                        <option value="">اختر محفظة المصدر</option>
                        @foreach(($transferWallets ?? collect()) as $walletOption)
                            <option value="{{ $walletOption->id }}" {{ (string) old('from_wallet_id') === (string) $walletOption->id ? 'selected' : '' }}>
                                {{ $walletOption->name }} ({{ number_format($walletOption->balance, 2) }} {{ $walletOption->currency ?? platform_currency() }})
                            </option>
                        @endforeach
                    </select>
                    @error('from_wallet_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="to_wallet_id">إلى محفظة</label>
                    <select id="to_wallet_id" name="to_wallet_id" class="{{ $fieldClass }}" required>
                        <option value="">اختر محفظة الوجهة</option>
                        @foreach(($transferWallets ?? collect()) as $walletOption)
                            <option value="{{ $walletOption->id }}" {{ (string) old('to_wallet_id') === (string) $walletOption->id ? 'selected' : '' }}>
                                {{ $walletOption->name }} ({{ number_format($walletOption->balance, 2) }} {{ $walletOption->currency ?? platform_currency() }})
                            </option>
                        @endforeach
                    </select>
                    @error('to_wallet_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="transfer_amount">المبلغ</label>
                    <input id="transfer_amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="{{ $fieldClass }}" placeholder="0.00" required>
                    @error('amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="transfer_notes">ملاحظات (اختياري)</label>
                    <input id="transfer_notes" type="text" name="notes" value="{{ old('notes') }}" class="{{ $fieldClass }}" placeholder="سبب التحويل أو مرجع العملية">
                    @error('notes')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                        <i class="fas fa-paper-plane text-xs"></i>
                        تنفيذ التحويل
                    </button>
                </div>
            </form>
        @else
            <div class="mx-4 mb-4 mt-0 rounded-xl border border-line bg-metal/15 px-4 py-3 text-sm text-metal sm:mx-5 sm:mb-5">
                تحتاج إلى محفظتين نشطتين على الأقل لتنفيذ التحويل.
            </div>
        @endif
    </article>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">نشاط الشهر الحالي</h3>
                        <p class="mt-0.5 text-xs text-muted">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">
                        <i class="fas fa-calendar-alt text-[10px]"></i>
                        {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div class="rounded-xl border border-line bg-accent-soft/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-accent">الإيداعات</p>
                            <i class="fas fa-arrow-down text-accent"></i>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-ink">
                            {{ number_format($currentMonthDeposits ?? 0, 2) }} <span class="text-sm font-normal text-muted">$</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas-muted/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-muted">السحوبات</p>
                            <i class="fas fa-arrow-up text-muted"></i>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-ink">
                            {{ number_format($currentMonthWithdrawals ?? 0, 2) }} <span class="text-sm font-normal text-muted">$</span>
                        </p>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">توزيع حسب النوع</h3>
                    <p class="mt-0.5 text-xs text-muted">عدد المحافظ والأرصدة لكل نوع</p>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    @forelse($typeDistribution as $type)
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex size-10 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                    <i class="fas fa-signal text-sm"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ $type['label'] }}</p>
                                    <p class="text-xs text-muted">{{ number_format($type['wallets_count']) }} محفظة</p>
                                </div>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-accent">
                                {{ number_format($type['total_balance'], 2) }} <span class="text-xs font-normal text-muted">$</span>
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-muted">لا توجد بيانات كافية حالياً.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">أحدث المحافظ المضافة</h3>
                <p class="mt-0.5 text-xs text-muted">آخر المحافظ التي تم إنشاؤها</p>
            </div>
            <div class="space-y-3 p-4 sm:p-5">
                @forelse($recentWallets as $recent)
                    <div class="rounded-xl border border-line bg-canvas/40 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $recent->name ?? 'محفظة بدون اسم' }}</p>
                            <span class="text-xs text-muted">{{ optional($recent->created_at)->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-accent">{{ $recent->type_name }}</p>
                        <p class="mt-1 text-xs text-muted">
                            {{ $recent->user?->name ?? 'غير مرتبط' }} · {{ $recent->user?->phone ?? 'بدون رقم' }}
                        </p>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-sm font-semibold tabular-nums text-ink">
                                {{ number_format($recent->balance, 2) }} <span class="text-xs font-normal text-muted">$</span>
                            </span>
                            <a href="{{ route('admin.wallets.show', $recent) }}" class="text-xs font-medium text-accent hover:underline">
                                تفاصيل <i class="fas fa-arrow-left text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted">لا توجد محافظ حديثة.</p>
                @endforelse
            </div>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">جميع المحافظ</h3>
                <p class="mt-0.5 text-xs text-muted">قائمة المحافظ المفعلة وغير المفعلة مع تفاصيل الاتصال والرصيد</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ number_format($wallets->total()) }} محفظة</span>
        </div>

        @if($wallets->count())
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
                @foreach($wallets as $wallet)
                    <div class="flex flex-col gap-4 rounded-2xl border border-line bg-canvas/30 p-5 transition hover:border-accent/20 hover:shadow-soft">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base font-semibold text-ink">{{ $wallet->name ?? 'محفظة بدون اسم' }}</h4>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $wallet->is_active ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                                        <span class="size-1.5 rounded-full {{ $wallet->is_active ? 'bg-accent' : 'bg-muted' }}"></span>
                                        {{ $wallet->is_active ? 'نشطة' : 'غير نشطة' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-accent">{{ $wallet->type_name }}</p>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $wallet->user?->name ?? $wallet->account_holder ?? 'غير محدد' }} · {{ $wallet->user?->phone ?? 'بدون رقم' }}
                                </p>
                            </div>
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent">
                                <i class="fas fa-wallet"></i>
                            </span>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-muted">الرصيد الحالي</dt>
                                <dd class="mt-0.5 font-semibold tabular-nums text-ink">{{ number_format($wallet->balance, 2) }} <span class="text-xs font-normal text-muted">$</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">الرصيد المعلّق</dt>
                                <dd class="mt-0.5 font-semibold tabular-nums text-ink">{{ number_format($wallet->pending_balance ?? 0, 2) }} <span class="text-xs font-normal text-muted">$</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">رقم الحساب</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $wallet->account_number ?? 'غير متوفر' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">البنك / القناة</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $wallet->bank_name ?? 'غير محدد' }}</dd>
                            </div>
                        </dl>

                        <div class="flex items-center justify-between text-xs text-muted">
                            <span>أضيفت {{ optional($wallet->created_at)->diffForHumans() }}</span>
                            <span>آخر تحديث {{ optional($wallet->updated_at)->diffForHumans() }}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.wallets.show', $wallet) }}" class="btn-press inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-accent-soft px-4 py-2 text-sm font-medium text-accent transition hover:bg-accent/10">
                                <i class="fas fa-eye text-xs"></i>
                                عرض التفاصيل
                            </a>
                            <a href="{{ route('admin.wallets.transactions', $wallet) }}" class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-ink transition hover:border-accent/30 hover:text-accent" title="سجل المعاملات">
                                <i class="fas fa-receipt text-xs"></i>
                            </a>
                            <a href="{{ route('admin.wallets.reports', $wallet) }}" class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-ink transition hover:border-accent/30 hover:text-accent" title="التقارير">
                                <i class="fas fa-chart-bar text-xs"></i>
                            </a>
                            <a href="{{ route('admin.wallets.edit', $wallet) }}" class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-ink transition hover:border-accent/30 hover:text-accent" title="تعديل">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <form action="{{ route('admin.wallets.destroy', $wallet) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إزالة هذه المحفظة؟ سيتم حذف المحفظة نهائياً.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-press inline-flex size-9 items-center justify-center rounded-xl border border-line text-rose-600 transition hover:border-rose-300 hover:bg-rose-50" title="إزالة المحفظة">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($wallets->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $wallets->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="px-4 py-16 text-center sm:px-5">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-inbox"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا توجد محافظ حتى الآن</p>
                <p class="mt-1 text-xs text-muted">يمكنك إنشاء محفظة جديدة من خلال الزر العلوي.</p>
            </div>
        @endif
    </article>
</div>
@endsection
