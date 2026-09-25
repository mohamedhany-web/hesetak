@extends('layouts.student-timeline')

@section('title', (app()->getLocale() === 'ar' ? 'طلب استشارة — ' : 'Consultation — ').$instructor->name)
@section('page_title', app()->getLocale() === 'ar' ? 'طلب استشارة' : 'Request consultation')

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $title = $isRtl ? 'طلب استشارة ودفع' : 'Request & pay';
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $title,
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => $isRtl ? 'الاستشارات' : 'Consultations', 'url' => route('consultations.index')],
        ['label' => $instructor->name, 'url' => null],
    ],
])

@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="st-flash st-flash--err">{{ $errors->first() }}</div>
@endif

<section class="st-msg-intro">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $isRtl
            ? 'حوّل على حسابات المنصة وارفع صورة الإيصال — بعد التأكيد يُحدد الموعد.'
            : 'Transfer to platform accounts and upload the receipt — after verification the session is scheduled.' }}</p>
    </div>
    <a href="{{ route('consultations.index') }}" class="st-pill st-pill--outline">{{ $isRtl ? 'طلباتي' : 'My requests' }}</a>
</section>

<section class="st-teacher-facts" aria-label="{{ $title }}">
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-user-tie"></i></span>
        <div>
            <strong>{{ $instructor->name }}</strong>
            <small>{{ $isRtl ? 'المعلم' : 'Teacher' }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-coins"></i></span>
        <div>
            <strong>{{ number_format((float) $priceEgp, 2) }} {{ currency_symbol() }}</strong>
            <small>{{ $isRtl ? 'قيمة الاستشارة' : 'Consultation fee' }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
        <div>
            <strong>{{ (int) $durationMinutes }}</strong>
            <small>{{ $isRtl ? 'دقيقة' : 'minutes' }}</small>
        </div>
    </article>
</section>

@if($settings->payment_instructions)
    <div class="st-flash" style="margin-top:1rem;white-space:pre-line">{{ $settings->payment_instructions }}</div>
@endif

<section class="st-settings-block" style="margin-top:1rem">
    <div class="st-settings-block__head">
        <span class="st-settings-block__icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
        <div>
            <h3>{{ $isRtl ? 'بيانات الدفع' : 'Payment details' }}</h3>
            <p>{{ $isRtl ? 'اختر طريقة التحويل وارفع الإيصال' : 'Choose a transfer method and upload the receipt' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('consultations.store', $instructor) }}" enctype="multipart/form-data" class="st-form" style="display:grid;gap:1rem;margin-top:1rem">
        @csrf

        <label class="st-field">
            <span class="st-field__label">{{ $isRtl ? 'موضوع أو استفسارك (اختياري)' : 'Topic or question (optional)' }}</span>
            <textarea name="student_message" rows="4" class="st-input" placeholder="{{ $isRtl ? 'صف بإيجاز ما تحتاجه…' : 'Briefly describe what you need…' }}">{{ old('student_message') }}</textarea>
        </label>

        <label class="st-field">
            <span class="st-field__label">{{ $isRtl ? 'طريقة الدفع' : 'Payment method' }}</span>
            <select name="payment_method" id="payment_method" class="st-input" required>
                <option value="">{{ $isRtl ? 'اختر طريقة الدفع' : 'Select method' }}</option>
                <option value="bank_transfer" @selected(old('payment_method')==='bank_transfer')>{{ $isRtl ? 'تحويل بنكي / محفظة' : 'Bank / e-wallet' }}</option>
                <option value="cash" @selected(old('payment_method')==='cash')>{{ $isRtl ? 'نقدي' : 'Cash' }}</option>
                <option value="other" @selected(old('payment_method')==='other')>{{ $isRtl ? 'أخرى' : 'Other' }}</option>
            </select>
        </label>

        @if(isset($availableWallets) && $availableWallets->count() > 0)
            <div id="wallet_selection" class="hidden" style="display:grid;gap:0.75rem">
                <label class="st-field">
                    <span class="st-field__label">{{ $isRtl ? 'حساب المنصة للتحويل' : 'Platform transfer account' }}</span>
                    <select name="wallet_id" id="wallet_id" class="st-input">
                        <option value="">{{ $isRtl ? 'اختر الحساب' : 'Select account' }}</option>
                        @foreach($availableWallets as $wallet)
                            <option value="{{ $wallet->id }}"
                                    @selected((string) old('wallet_id') === (string) $wallet->id)
                                    data-type="{{ $wallet->type }}"
                                    data-name="{{ $wallet->name }}"
                                    data-account-number="{{ $wallet->account_number }}"
                                    data-bank-name="{{ $wallet->bank_name }}"
                                    data-account-holder="{{ $wallet->account_holder }}"
                                    data-notes="{{ $wallet->notes }}">
                                {{ $wallet->name ?? \App\Models\Wallet::typeLabel($wallet->type) }}
                                @if($wallet->account_number) — {{ $wallet->account_number }} @endif
                            </option>
                        @endforeach
                    </select>
                </label>

                <div id="wallet_details" class="hidden st-flash st-flash--ok" style="display:none">
                    <p style="margin:0 0 0.5rem;font-weight:800">{{ $isRtl ? 'تفاصيل التحويل' : 'Transfer details' }}</p>
                    <p style="margin:0"><span class="muted">{{ $isRtl ? 'النوع' : 'Type' }}:</span> <span id="wallet_type_text"></span></p>
                    <p id="wallet_name_detail" class="hidden" style="margin:0.25rem 0 0"><span class="muted">{{ $isRtl ? 'الاسم' : 'Name' }}:</span> <span id="wallet_name_text"></span></p>
                    <p id="wallet_account_detail" class="hidden" style="margin:0.25rem 0 0"><span class="muted">{{ $isRtl ? 'رقم الحساب' : 'Account' }}:</span> <span id="wallet_account_text" dir="ltr"></span></p>
                    <p id="wallet_bank_detail" class="hidden" style="margin:0.25rem 0 0"><span class="muted">{{ $isRtl ? 'البنك' : 'Bank' }}:</span> <span id="wallet_bank_text"></span></p>
                    <p id="wallet_holder_detail" class="hidden" style="margin:0.25rem 0 0"><span class="muted">{{ $isRtl ? 'صاحب الحساب' : 'Holder' }}:</span> <span id="wallet_holder_text"></span></p>
                    <p id="wallet_notes_detail" class="hidden" style="margin:0.5rem 0 0"><span id="wallet_notes_text"></span></p>
                    <p style="margin:0.75rem 0 0;font-size:13px;font-weight:700">
                        {{ $isRtl ? 'حوّل' : 'Transfer' }}
                        <strong>{{ number_format((float) $priceEgp, 2) }} {{ currency_symbol() }}</strong>
                        {{ $isRtl ? 'ثم ارفع الإيصال.' : 'then upload the receipt.' }}
                    </p>
                </div>
            </div>
        @endif

        <label class="st-field">
            <span class="st-field__label">{{ $isRtl ? 'صورة الإيصال' : 'Receipt image' }} *</span>
            <input type="file" name="payment_proof" accept="image/*" class="st-input" required>
            <span class="st-field__hint">jpeg / png / jpg</span>
        </label>

        <label class="st-field">
            <span class="st-field__label">{{ $isRtl ? 'مرجع التحويل (اختياري)' : 'Payment reference (optional)' }}</span>
            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="st-input" placeholder="{{ $isRtl ? 'رقم العملية' : 'Transaction id' }}">
        </label>

        <div style="display:flex;flex-wrap:wrap;gap:0.65rem">
            <button type="submit" class="st-pill st-pill--solid">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                {{ $isRtl ? 'إرسال الطلب والإيصال' : 'Submit request' }}
            </button>
            <a href="{{ route('consultations.index') }}" class="st-pill st-pill--outline">{{ $isRtl ? 'إلغاء' : 'Cancel' }}</a>
        </div>
    </form>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var paymentMethod = document.getElementById('payment_method');
    var walletSelection = document.getElementById('wallet_selection');
    var walletId = document.getElementById('wallet_id');
    var walletDetails = document.getElementById('wallet_details');
    if (!paymentMethod || !walletSelection) return;

    function syncWalletVisibility() {
        var show = paymentMethod.value === 'bank_transfer' || paymentMethod.value === 'other';
        walletSelection.classList.toggle('hidden', !show);
        walletSelection.style.display = show ? 'grid' : 'none';
        if (!show) {
            if (walletDetails) {
                walletDetails.classList.add('hidden');
                walletDetails.style.display = 'none';
            }
            if (walletId) walletId.value = '';
        }
    }
    paymentMethod.addEventListener('change', syncWalletVisibility);
    syncWalletVisibility();

    if (walletId && walletDetails) {
        walletId.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (!this.value || !opt) {
                walletDetails.classList.add('hidden');
                walletDetails.style.display = 'none';
                return;
            }
            var type = opt.getAttribute('data-type');
            var typeLabels = { vodafone_cash: 'فودافون كاش', instapay: 'إنستا باي', bank_transfer: 'تحويل بنكي', cash: 'كاش', other: 'أخرى' };
            document.getElementById('wallet_type_text').textContent = typeLabels[type] || type || '—';
            function toggleLine(prefix, val) {
                var row = document.getElementById(prefix + '_detail');
                var text = document.getElementById(prefix + '_text');
                if (!row || !text) return;
                if (val) { row.classList.remove('hidden'); row.style.display = ''; text.textContent = val; }
                else { row.classList.add('hidden'); row.style.display = 'none'; }
            }
            toggleLine('wallet_name', opt.getAttribute('data-name'));
            toggleLine('wallet_account', opt.getAttribute('data-account-number'));
            toggleLine('wallet_bank', opt.getAttribute('data-bank-name'));
            toggleLine('wallet_holder', opt.getAttribute('data-account-holder'));
            var notes = opt.getAttribute('data-notes');
            var notesRow = document.getElementById('wallet_notes_detail');
            if (notes) { notesRow.classList.remove('hidden'); notesRow.style.display = ''; document.getElementById('wallet_notes_text').textContent = notes; }
            else { notesRow.classList.add('hidden'); notesRow.style.display = 'none'; }
            walletDetails.classList.remove('hidden');
            walletDetails.style.display = 'block';
        });
    }
});
</script>
@endpush
@endsection
