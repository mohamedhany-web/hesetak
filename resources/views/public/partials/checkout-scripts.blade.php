    <script>
    (function(){
        function scrollProgress(){
            var s=window.pageYOffset||document.documentElement.scrollTop;
            var h=document.documentElement.scrollHeight-window.innerHeight;
            var b=document.getElementById('scroll-progress');
            if(b) b.style.width=(h>0?(s/h)*100:0)+'%';
        }
        window.addEventListener('scroll',scrollProgress,{passive:true});
        function initReveal(){
            var t=document.querySelectorAll('.reveal');
            if(!t.length)return;
            var o=new IntersectionObserver(function(e){
                e.forEach(function(n){if(n.isIntersecting){n.target.classList.add('revealed');o.unobserve(n.target);}});
            },{threshold:.08,rootMargin:'0px 0px -40px 0px'});
            t.forEach(function(el){o.observe(el);});
        }
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){scrollProgress();initReveal();});
        else{scrollProgress();initReveal();}
    })();
    </script>
    <script>
    (function(){
        var panel = document.getElementById('checkout-discount-panel');
        var summary = document.getElementById('checkout-pricing-summary');
        if (!panel || !summary) return;
        var quoteUrl = panel.getAttribute('data-quote-url');
        var meta = document.querySelector('meta[name="csrf-token"]');
        var csrf = (meta && meta.getAttribute('content')) || '';
        function el(id){ return document.getElementById(id); }
        function fmt(n){
            var x = parseFloat(n);
            if (isNaN(x)) x = 0;
            return x.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function setMsg(text, isErr) {
            var m = el('checkout_pricing_msg');
            if (!m) return;
            m.textContent = text || '';
            m.classList.toggle('hidden', !text);
            m.classList.toggle('text-red-300', !!isErr);
            m.classList.toggle('text-emerald-300', !isErr && !!text);
            m.style.color = isErr ? '#991B1B' : '#059669';
        }
        function updateSummary(data) {
            var base = parseFloat(summary.getAttribute('data-base-price')) || 0;
            var isMonthly = summary.getAttribute('data-is-monthly') === '1';
            var curLabel = @json(currency_symbol());
            var egpSmall = ' <span style="font-size:.75rem;font-weight:700;color:#8A94A6">' + curLabel + '</span>' + (isMonthly ? ' <span style="font-size:.7rem;font-weight:700;color:#8A94A6">/{{ __('public.per_month') }}</span>' : '');
            var egpLarge = ' <span style="font-size:.8rem;font-weight:700;color:#8A94A6">' + curLabel + '</span>' + (isMonthly ? ' <span style="font-size:.75rem;font-weight:700;color:#8A94A6">/{{ __('public.per_month') }}</span>' : '');
            if (!data || !data.ok) {
                el('sum-original').innerHTML = fmt(base) + egpSmall;
                el('sum-final').innerHTML = fmt(base) + egpLarge;
                el('sum-coupon-row').classList.add('hidden');
                el('sum-wallet-row').classList.add('hidden');
                return;
            }
            el('sum-original').innerHTML = fmt(data.original_amount) + egpSmall;
            if (data.discount_amount > 0) {
                el('sum-coupon-row').classList.remove('hidden');
                el('sum-coupon').textContent = '− ' + fmt(data.discount_amount) + ' ' + curLabel;
            } else {
                el('sum-coupon-row').classList.add('hidden');
            }
            if (data.wallet_credit_amount > 0) {
                el('sum-wallet-row').classList.remove('hidden');
                el('sum-wallet').textContent = '− ' + fmt(data.wallet_credit_amount) + ' ' + curLabel;
            } else {
                el('sum-wallet-row').classList.add('hidden');
            }
            el('sum-final').innerHTML = fmt(data.final_amount) + egpLarge;
        }
        function quote() {
            setMsg('', false);
            var fd = new FormData();
            fd.append('_token', csrf);
            var c = el('checkout_coupon_code');
            var w = el('checkout_wallet_credit');
            var cur = el('checkout_currency');
            fd.append('coupon_code', c ? (c.value || '').trim() : '');
            fd.append('wallet_credit', w && w.value !== '' ? w.value : '0');
            fd.append('currency', @json(platform_currency()));
            var ar = document.querySelector('input[name="auto_renew"]');
            if (ar && ar.checked) { fd.append('auto_renew', '1'); }
            return fetch(quoteUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
                credentials: 'same-origin'
            }).then(function(r){
                return r.json().then(function(j){ return { ok: r.ok, status: r.status, data: j }; });
            }).then(function(res){
                if (res.ok && res.data && res.data.ok) {
                    updateSummary(res.data);
                    setMsg('تم تحديث السعر.', false);
                    var hfC = el('form_coupon_code');
                    var hfW = el('form_wallet_credit');
                    var hfCur = el('form_currency');
                    if (hfC) hfC.value = (el('checkout_coupon_code').value || '').trim();
                    if (hfW) {
                        var wIn = el('checkout_wallet_credit');
                        hfW.value = wIn && wIn.value !== '' ? wIn.value : '0';
                    }
                    if (hfCur) hfCur.value = @json(platform_currency());
                    if (typeof window.muallimxOnCheckoutPricingUpdated === 'function') {
                        try { window.muallimxOnCheckoutPricingUpdated(res.data); } catch (e) {}
                    }
                    return res.data;
                }
                var msg = (res.data && res.data.message) ? res.data.message : 'تعذّر حساب السعر.';
                setMsg(msg, true);
                updateSummary(null);
                return null;
            }).catch(function(){
                setMsg('خطأ في الاتصال.', true);
                updateSummary(null);
                return null;
            });
        }
        var btn = el('checkout_apply_pricing');
        if (btn) btn.addEventListener('click', function(e){ e.preventDefault(); quote(); });
        var curSel = el('checkout_currency');
        if (curSel) curSel.addEventListener('change', function(){ quote(); });
        var form = el('manual-checkout-form');
        if (form) {
            form.addEventListener('submit', function(){
                var c = el('checkout_coupon_code');
                var w = el('checkout_wallet_credit');
                var cur = el('checkout_currency');
                if (el('form_coupon_code')) el('form_coupon_code').value = c ? (c.value || '').trim() : '';
                if (el('form_wallet_credit')) el('form_wallet_credit').value = w && w.value !== '' ? w.value : '0';
                if (el('form_currency')) el('form_currency').value = @json(platform_currency());
            });
        }
        var oc = el('checkout_coupon_code');
        var ow = el('checkout_wallet_credit');
        if (el('form_coupon_code') && el('form_coupon_code').value && oc) oc.value = el('form_coupon_code').value;
        if (ow && el('form_wallet_credit') && el('form_wallet_credit').value) ow.value = el('form_wallet_credit').value;
        quote();
    })();
    </script>
    @if(!empty($fawaterakUseGateway) && empty($fawaterakMisconfigured) && ($fawaterakIntegration ?? 'iframe') === 'iframe')
    <script>
    (function(){
        var prepareUrl = @json(route('public.course.checkout.fawaterak.prepare', $course->id));
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = (meta && meta.getAttribute('content')) || @json(csrf_token());
        var errEl = document.getElementById('fawaterk-checkout-error');
        function showErr(msg) {
            if (!errEl) { alert(msg); return; }
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
        }
        function waitForFawaterkFn(resolve, reject) {
            window.requestAnimationFrame(function() {
                if (typeof fawaterkCheckout === 'function') {
                    resolve();
                } else {
                    setTimeout(function() {
                        if (typeof fawaterkCheckout === 'function') {
                            resolve();
                        } else {
                            reject(new Error('no_fn'));
                        }
                    }, 80);
                }
            });
        }
        function loadScriptTag(url) {
            return new Promise(function(resolve, reject) {
                var s = document.createElement('script');
                s.src = url;
                s.async = true;
                s.onload = function() { waitForFawaterkFn(resolve, reject); };
                s.onerror = function() { reject(new Error('network')); };
                document.head.appendChild(s);
            });
        }
        function loadScriptViaBlob(url) {
            return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
                .then(function(r) {
                    if (!r.ok) {
                        throw new Error('fetch ' + r.status);
                    }
                    return r.text();
                })
                .then(function(code) {
                    if (!code || code.trim().indexOf('<') === 0) {
                        throw new Error('not_js');
                    }
                    var blob = new Blob([code], { type: 'application/javascript' });
                    var blobUrl = URL.createObjectURL(blob);
                    return new Promise(function(resolve, reject) {
                        var s = document.createElement('script');
                        s.onload = function() {
                            URL.revokeObjectURL(blobUrl);
                            waitForFawaterkFn(resolve, reject);
                        };
                        s.onerror = function() {
                            URL.revokeObjectURL(blobUrl);
                            reject(new Error('blob_load'));
                        };
                        s.src = blobUrl;
                        document.head.appendChild(s);
                    });
                });
        }
        function loadScript(src) {
            var sep = src.indexOf('?') >= 0 ? '&' : '?';
            var url = src + sep + '_fk=' + Date.now();
            return loadScriptTag(url).catch(function() {
                return loadScriptViaBlob(url);
            });
        }
        function parseJsonSafe(text) {
            try { return JSON.parse(text); } catch (e) { return null; }
        }
        function run() {
            var fd = new FormData();
            fd.append('_token', token);
            var cEl = document.getElementById('checkout_coupon_code');
            var wEl = document.getElementById('checkout_wallet_credit');
            fd.append('coupon_code', cEl ? (cEl.value || '').trim() : '');
            fd.append('wallet_credit', wEl && wEl.value !== '' ? wEl.value : '0');
            var curEl = document.getElementById('checkout_currency');
            fd.append('currency', @json(platform_currency()));
            var arEl = document.querySelector('input[name="auto_renew"]');
            if (arEl && arEl.checked) { fd.append('auto_renew', '1'); }
            fetch(prepareUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd,
                credentials: 'same-origin'
            })
            .then(function(r) {
                return r.text().then(function(text) {
                    var data = parseJsonSafe(text);
                    return { ok: r.ok, status: r.status, data: data, raw: text };
                });
            })
            .then(function(res) {
                if (res.status === 401) {
                    showErr('انتهت الجلسة أو لم يُعاد تسجيل الدخول. حدّث الصفحة وسجّل الدخول ثم أعد المحاولة.');
                    return;
                }
                if (res.status === 419) {
                    showErr('انتهت صلاحية الجلسة الأمنية (CSRF). حدّث الصفحة بالكامل (F5) ثم أعد المحاولة.');
                    return;
                }
                if (!res.data) {
                    var rawLower = (res.raw || '').toLowerCase();
                    if (rawLower.indexOf('csrf') !== -1 || rawLower.indexOf('login') !== -1 || rawLower.indexOf('تسجيل الدخول') !== -1 || rawLower.indexOf('<!doctype') !== -1) {
                        showErr('انتهت جلسة تسجيل الدخول أو أُعيد توجيهك لصفحة أخرى. حدّث الصفحة (F5) وسجّل الدخول من جديد ثم افتح صفحة الدفع.');
                        return;
                    }
                    showErr('استجابة غير متوقعة من الخادم (رمز HTTP ' + res.status + '). راجع سجلات الخادم (laravel.log) أو إعدادات الجلسة على الإنتاج.');
                    return;
                }
                if (!res.ok) {
                    showErr(res.data.message || ('تعذّر تجهيز الدفع (رمز ' + res.status + ').'));
                    return;
                }
                if (res.data.mode === 'completed' && res.data.redirect) {
                    window.location.href = res.data.redirect;
                    return;
                }
                if ((res.data.mode && res.data.mode !== 'iframe') || !res.data.pluginScriptUrl || !res.data.pluginConfig) {
                    showErr('استجابة غير صالحة من الخادم (تأكد أن FAWATERAK_INTEGRATION=iframe).');
                    return;
                }
                return loadScript(res.data.pluginScriptUrl)
                    .then(function() {
                        // إلزامي: سكربت فواتيرك يستدعي getEnvUrl() وhandlePaymentProcess() ويعتمد على المتغير العام pluginConfig (ليس الوسيط فقط)
                        var cfg = res.data.pluginConfig;
                        window.pluginConfig = cfg;
                        fawaterkCheckout(cfg);
                    })
                    .catch(function(err) {
                        var msg;
                        if (err && err.message === 'no_fn') {
                            msg = 'وصل ملف فواتيرك لكن لم تُعرَّف الدالة fawaterkCheckout. راجع Console (CSP أو حظر إضافة).';
                        } else if (err && (err.name === 'ReferenceError' || (err.message && err.message.indexOf('pluginConfig') !== -1))) {
                            msg = 'خطأ في تهيئة فواتيرك: ' + (err.message || err.name) + '. إن ظهر بعد تحديث اليوم أبلغ الدعم.';
                        } else if (err && err.message && err.message.indexOf('network') === -1) {
                            msg = 'تعذّر تشغيل الدفع: ' + err.message;
                        } else {
                            msg = 'تعذّر تحميل ملف الدفع. جرّب بدون إضافات حجب، أو تعطيل الكاش في Network. المسار: /js/checkout-pay-widget.v1.js';
                        }
                        showErr(msg);
                    });
            })
            .catch(function() {
                showErr('تعذّر إكمال الطلب مع الخادم (انقطاع الشبكة أو خطأ غير متوقع). حدّث الصفحة (F5) أو راجع تبويب Network في أدوات المطوّر.');
            });
        }
        window.muallimxOnCheckoutPricingUpdated = run;
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
        else run();
    })();
    </script>
    @endif

    @if(!empty($fawaterakUseGateway) && empty($fawaterakMisconfigured) && ($fawaterakIntegration ?? 'iframe') === 'api')
    <script>
    (function(){
        var prepareUrl = @json(route('public.course.checkout.fawaterak.prepare', $course->id));
        var methodsUrl = @json(route('public.course.checkout.fawaterak.methods', $course->id));
        var payUrl = @json(route('public.course.checkout.fawaterak.pay', $course->id));
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = (meta && meta.getAttribute('content')) || @json(csrf_token());
        var errEl = document.getElementById('fawaterk-api-error');
        var loadEl = document.getElementById('fawaterk-api-loading');
        var methodsEl = document.getElementById('fawaterk-api-methods');
        var payBtn = document.getElementById('fawaterk-api-pay-btn');
        var resultEl = document.getElementById('fawaterk-api-result');
        var walletWrap = document.getElementById('fawaterk-api-wallet-wrap');
        var walletInput = document.getElementById('fawaterk-api-wallet');
        var selectedId = null;

        function showErr(msg) {
            if (!errEl) { alert(msg); return; }
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
        }
        function parseJsonSafe(text) {
            try { return JSON.parse(text); } catch (e) { return null; }
        }
        function renderMethods(list) {
            if (!methodsEl) return;
            methodsEl.innerHTML = '';
            list.forEach(function(m) {
                var id = m.paymentId;
                var name = (document.documentElement.getAttribute('dir') === 'rtl' && m.name_ar) ? m.name_ar : (m.name_en || m.name_ar || ('#' + id));
                var card = document.createElement('button');
                card.type = 'button';
                card.className = 'flex items-center gap-3 p-3 rounded-xl border-2 border-[#D7DDE6] bg-white text-start hover:border-[#0B3D91]/40 transition-colors';
                card.setAttribute('data-pid', String(id));
                if (m.logo && typeof m.logo === 'string') {
                    var img = document.createElement('img');
                    img.src = m.logo;
                    img.alt = '';
                    img.className = 'h-10 w-auto object-contain shrink-0';
                    img.loading = 'lazy';
                    card.appendChild(img);
                } else {
                    var ph = document.createElement('span');
                    ph.className = 'w-10 h-10 rounded-xl bg-[#E8EEF8] flex items-center justify-center text-[#0B3D91] shrink-0';
                    ph.innerHTML = '<i class="fas fa-credit-card"></i>';
                    card.appendChild(ph);
                }
                var title = document.createElement('span');
                title.className = 'font-bold text-[#0B1220] flex-1 min-w-0';
                title.textContent = name;
                card.appendChild(title);
                card.addEventListener('click', function() {
                    methodsEl.querySelectorAll('button').forEach(function(b) {
                        b.classList.remove('border-[#F5B800]', 'ring-2', 'ring-[#F5B800]/30');
                        b.classList.add('border-[#D7DDE6]');
                    });
                    card.classList.remove('border-[#D7DDE6]');
                    card.classList.add('border-[#F5B800]', 'ring-2', 'ring-[#F5B800]/30');
                    selectedId = id;
                    if (payBtn) payBtn.disabled = false;
                });
                methodsEl.appendChild(card);
            });
            methodsEl.classList.remove('hidden');
            if (walletWrap) walletWrap.classList.remove('hidden');
        }
        function showPaymentResult(pd) {
            if (!resultEl || !pd) return;
            resultEl.classList.remove('hidden');
            var html = '';
            if (pd.redirectTo) {
                window.location.href = pd.redirectTo;
                return;
            }
            if (pd.fawryCode) html += '<p><strong>رمز فوري:</strong> <span dir="ltr">' + pd.fawryCode + '</span></p>';
            if (pd.expireDate) html += '<p class="text-slate-600 text-xs">ينتهي: ' + pd.expireDate + '</p>';
            if (pd.meezaReference != null) html += '<p><strong>مرجع ميزا:</strong> ' + pd.meezaReference + '</p>';
            if (pd.meezaQrCode) html += '<p class="break-all text-xs" dir="ltr">' + pd.meezaQrCode + '</p>';
            if (pd.amanCode) html += '<p><strong>أمان:</strong> ' + pd.amanCode + '</p>';
            if (pd.masaryCode) html += '<p><strong>مصاري:</strong> ' + pd.masaryCode + '</p>';
            if (!html) html = '<pre class="text-xs whitespace-pre-wrap break-all" dir="ltr">' + JSON.stringify(pd, null, 2) + '</pre>';
            resultEl.innerHTML = '<p class="font-bold text-acad-yellow mb-2">أكمل الدفع حسب التعليمات:</p>' + html +
                '<p class="text-xs text-white/45 mt-3">بعد الدفع قد تُعاد إلى الموقع تلقائياً؛ إن لم يحدث ذلك حدّث صفحة الطلبات.</p>';
        }
        function run() {
            var fd = new FormData();
            fd.append('_token', token);
            var cEl = document.getElementById('checkout_coupon_code');
            var wEl = document.getElementById('checkout_wallet_credit');
            fd.append('coupon_code', cEl ? (cEl.value || '').trim() : '');
            fd.append('wallet_credit', wEl && wEl.value !== '' ? wEl.value : '0');
            var curEl = document.getElementById('checkout_currency');
            fd.append('currency', @json(platform_currency()));
            var arEl = document.querySelector('input[name="auto_renew"]');
            if (arEl && arEl.checked) { fd.append('auto_renew', '1'); }
            fetch(prepareUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, status: r.status, data: parseJsonSafe(t), raw: t }; }); })
            .then(function(res) {
                if (res.status === 401) { showErr('انتهت الجلسة. سجّل الدخول ثم أعد فتح الصفحة.'); return; }
                if (res.status === 419) { showErr('انتهت صلاحية الجلسة (CSRF). حدّث الصفحة (F5).'); return; }
                if (!res.data || !res.ok) {
                    showErr((res.data && res.data.message) || 'تعذّر تجهيز الطلب.');
                    return;
                }
                if (res.data.mode !== 'api') {
                    showErr('الخادم ليس في وضع API. ضبط FAWATERAK_INTEGRATION=api في .env');
                    return;
                }
                return fetch(methodsUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
            })
            .then(function(r) {
                if (!r) return;
                return r.text().then(function(t) { return { ok: r.ok, status: r.status, data: parseJsonSafe(t), raw: t }; });
            })
            .then(function(res) {
                if (!res) return;
                if (loadEl) loadEl.classList.add('hidden');
                if (!res.ok || !res.data || res.data.status !== 'success' || !Array.isArray(res.data.data)) {
                    showErr((res.data && res.data.message) || 'تعذّر جلب وسائل الدفع.');
                    return;
                }
                renderMethods(res.data.data);
            })
            .catch(function() {
                if (loadEl) loadEl.classList.add('hidden');
                showErr('تعذّر الاتصال بالخادم.');
            });
        }

        window.muallimxOnCheckoutPricingUpdated = function () {
            if (loadEl) loadEl.classList.remove('hidden');
            if (methodsEl) {
                methodsEl.classList.add('hidden');
                methodsEl.innerHTML = '';
            }
            if (walletWrap) walletWrap.classList.add('hidden');
            if (resultEl) {
                resultEl.classList.add('hidden');
                resultEl.innerHTML = '';
            }
            if (payBtn) {
                payBtn.disabled = true;
            }
            selectedId = null;
            if (errEl) errEl.classList.add('hidden');
            run();
        };

        if (payBtn) {
            payBtn.addEventListener('click', function() {
                if (!selectedId) return;
                errEl && errEl.classList.add('hidden');
                payBtn.disabled = true;
                var body = { payment_method_id: selectedId };
                var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
                if (w) body.mobile_wallet_number = w;
                fetch(payUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                })
                .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, status: r.status, data: parseJsonSafe(t), raw: t }; }); })
                .then(function(res) {
                    payBtn.disabled = false;
                    if (res.status === 401 || res.status === 419) {
                        showErr('انتهت الجلسة. حدّث الصفحة وسجّل الدخول.');
                        return;
                    }
                    if (!res.data) { showErr('استجابة غير متوقعة من الخادم.'); return; }
                    if (!res.ok) {
                        showErr(res.data.message || 'تعذّر بدء الدفع.');
                        return;
                    }
                    var pd = res.data.data && res.data.data.payment_data;
                    showPaymentResult(pd);
                })
                .catch(function() {
                    payBtn.disabled = false;
                    showErr('تعذّر إكمال الطلب.');
                });
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
        else run();
    })();
    </script>
    @endif
