# بوابة الدفع «فواتيرك» (Fawaterak) — شرح الربط الحالي بالكامل (حصتك)

هذا الملف يوثق **الربط الحالي كما هو مطبق في الكود**: مسارات الكورس والاشتراك، وضع `iframe` ووضع `api`، الجلسة (Session)، الحقول التي تُخزن في قاعدة البيانات، وكيف يتم اعتماد الطلب وإنشاء `Invoice/Payment/Transaction` بعد العودة من فواتيرك.

> تنبيه: لا تضع أي مفاتيح حقيقية داخل ملفات التوثيق. استخدم `.env` فقط.

---

## نقاط الربط الأساسية (TL;DR)

- **تفعيل البوابة**: يتم عبر إعداد في قاعدة البيانات `settings` باسم `fawaterak_gateway_enabled` (مقروء من `App\Services\PaymentGatewaySettings`).
- **وضع الربط**:
  - `FAWATERAK_INTEGRATION=iframe`: سكربت فواتيرك + HMAC.
  - `FAWATERAK_INTEGRATION=api`: REST API (Bearer) (`getPaymentmethods` + `invoiceInitPay`).
- **الاعتماد النهائي** (تفعيل الكورس/الاشتراك وإنشاء الفاتورة المحلية) يحدث عند:
  - `GET /checkout/fawaterak/success`
  - ويعتمد على وجود ID محفوظ في الـ session.

---

## 1) الملفات المسؤولة عن فواتيرك

### إعدادات/خدمات

- `config/fawaterak.php`: ربط متغيرات `.env` بإعدادات فواتيرك.
- `app/Services/FawaterakService.php` (iframe):
  - `pluginScriptUrl()`: رابط سكربت فواتيرك بحسب البيئة.
  - `domainForHash()`: الدومين الذي يدخل في HMAC.
  - `generateHashKey()`: حساب `hash_hmac` المطلوب للإضافة.
- `app/Services/FawaterakApiService.php` (api):
  - `baseUrl()` + `getPaymentMethods()` + `invoiceInitPay()`.
- `app/Services/PaymentGatewaySettings.php`:
  - `isFawaterakEnabled()`: هل البوابة مفعلة؟
  - `computeFeeSplit()`: حساب عمولة بوابة الدفع (`payment_gateway_fee_percent`).

### الكنترولرز

- كورسات:
  - `app/Http/Controllers/Public/CheckoutController.php`
    - `fawaterakPrepare()`, `fawaterakPaymentMethods()`, `fawaterakPay()`, `fawaterakReturn()`
- اشتراكات المعلم:
  - `app/Http/Controllers/Public/SubscriptionCheckoutController.php`
    - `fawaterakPrepare()`, `fawaterakPaymentMethods()`, `fawaterakPay()`
- بروكسي سكربت الإضافة (iframe):
  - `app/Http/Controllers/Public/FawaterkPluginController.php`

### الواجهات

- كورس: `resources/views/public/checkout.blade.php`
- اشتراك: `resources/views/public/subscription-checkout.blade.php`

---

## 2) المسارات (Routes) الحالية

### 2.1 شراء كورس

- `POST /course/{courseId}/checkout/fawaterak/prepare`
  - اسم: `public.course.checkout.fawaterak.prepare`
- (API فقط) `GET /course/{courseId}/checkout/fawaterak/methods`
  - اسم: `public.course.checkout.fawaterak.methods`
- (API فقط) `POST /course/{courseId}/checkout/fawaterak/pay`
  - اسم: `public.course.checkout.fawaterak.pay`

### 2.2 اشتراك المعلم

- `POST /pricing/checkout/{plan}/fawaterak/prepare`
  - اسم: `public.subscription.checkout.fawaterak.prepare`
- (API فقط) `GET /pricing/checkout/{plan}/fawaterak/methods`
  - اسم: `public.subscription.checkout.fawaterak.methods`
- (API فقط) `POST /pricing/checkout/{plan}/fawaterak/pay`
  - اسم: `public.subscription.checkout.fawaterak.pay`

### 2.3 مسار العودة الموحد (نجاح/فشل/معلق)

- `GET /checkout/fawaterak/{status}`
  - اسم: `public.checkout.fawaterak.return`
  - `status`: `success|fail|pending`

### 2.4 بروكسي سكربت الإضافة (iframe)

- `GET /js/checkout-pay-widget.v1.js`
  - اسم: `public.fawaterk.plugin`
- `GET /fawaterk/plugin.min.js` (Redirect 301)
  - إلى `/js/checkout-pay-widget.v1.js`

---

## 3) متغيرات `.env` الخاصة بفواتيرك

المرجع: `.env.example` و `config/fawaterak.php`.

### 3.1 عام

- `FAWATERAK_INTEGRATION`: `iframe` أو `api`
- `FAWATERAK_ENV`: `test` أو `live`
- `FAWATERAK_CURRENCY`: (افتراضي `EGP`)
- `FAWATERAK_VERSION`: (افتراضي `0`)

### 3.2 وضع iframe (الإضافة + HMAC)

- `FAWATERAK_VENDOR_KEY` (مطلوب)
- `FAWATERAK_PROVIDER_KEY` (مطلوب)
- `FAWATERAK_IFRAME_DOMAIN` (اختياري مهم)
  - إن كان فارغاً: يتم اشتقاقه كـ `https://<host(APP_URL)>`
  - إن كان هناك اختلاف `www`/بيئة محلية/دومين مسجل في لوحة فواتيرك: اضبطه صراحة
- `FAWATERAK_PLUGIN_BEARER_TOKEN` (اختياري)
  - إن كان فارغاً: الكود يستخدم `FAWATERAK_VENDOR_KEY` كـ `token` للإضافة
- `FAWATERAK_TEST_PLUGIN_URL` و `FAWATERAK_LIVE_PLUGIN_URL` (اختياري)

### 3.3 وضع api (Gateway REST)

- `FAWATERAK_API_TOKEN` (مطلوب في وضع api)
- `FAWATERAK_API_BASE_URL` (اختياري)
- `FAWATERAK_API_TIMEOUT` (اختياري)

بعد أي تعديل على `.env`:

```bash
php artisan config:clear
```

---

## 4) تدفق شراء كورس عبر فواتيرك (تفصيلي)

### 4.1 خطوة `prepare` (تجهيز الدفع)

نداء: `POST /course/{courseId}/checkout/fawaterak/prepare`

داخل `CheckoutController::fawaterakPrepare`:

- يتحقق من تفعيل البوابة (`PaymentGatewaySettings::isFawaterakEnabled()`).
- يحدد وضع الربط من `FawaterakApiService::integrationMode()`:
  - `api`: يتأكد من وجود `FAWATERAK_API_TOKEN`
  - `iframe`: يتأكد من وجود `FAWATERAK_VENDOR_KEY` و`FAWATERAK_PROVIDER_KEY`
- ينشئ أو يحدّث `Order` بحالة `pending` مع:
  - `payment_method=online`
  - `payment_proof=null`
  - وحفظ بيانات كوبون/رصيد محفظة إن وجدت
- يحفظ `fawaterak_order_id` في session.

**ناتج `prepare` حسب الوضع**:

- `mode=iframe`:
  - `pluginScriptUrl`: دائماً يعود إلى `public.fawaterk.plugin` (بروكسي داخل نطاق الموقع)
  - `pluginConfig`: يحوي `hashKey` و `token` و `requestBody` و `redirectionUrls`
- `mode=api`:
  - `methodsUrl` و `payUrl`
- `mode=completed` (حالة خاصة): إن أصبح المبلغ النهائي \(≈ 0\) بسبب كوبون/محفظة:
  - يعتمد الطلب محلياً ويعيد `redirect` لصفحة الكورس.

### 4.2 وضع iframe (في الواجهة)

في `resources/views/public/checkout.blade.php`:

- POST `prepare`
- تحميل السكربت من `/js/checkout-pay-widget.v1.js`
- تعيين `window.pluginConfig = pluginConfig` ثم استدعاء `fawaterkCheckout(pluginConfig)`
- بعد الدفع فواتيرك تعيد المتصفح إلى:
  - `GET /checkout/fawaterak/success|fail|pending`

### 4.3 وضع api (في الواجهة)

في `checkout.blade.php`:

- POST `prepare` → ثم GET `methods` → عرض الطرق
- POST `pay` بـ:
  - `payment_method_id` (إلزامي)
  - `mobile_wallet_number` (اختياري لبعض الوسائل)
- النتيجة قد تحتوي:
  - `redirectTo` (تحويل) أو
  - أكواد دفع (فوري/ميزا/…)
- بعد اكتمال الدفع غالباً يحصل redirect إلى مسار `success`.

### 4.4 مسار العودة (الاعتماد النهائي)

نداء: `GET /checkout/fawaterak/{status}`

داخل `CheckoutController::fawaterakReturn` (فرع الكورس):

- يقرأ `fawaterak_order_id` من session
- `fail`: يمسح session ويرجع لصفحة checkout برسالة
- `pending`: رسالة انتظار
- `success`:
  - `lockForUpdate` على order
  - يستدعي `approveOrderAfterOnlinePayment(...)` لإنشاء:
    - `Invoice` (مدفوعة)
    - `Payment` (completed) + عمولة بوابة الدفع
    - `Transaction` (قيد credit + قيد fee عند وجود عمولة)
  - يفعّل تسجيل الطالب في الكورس
  - يمسح session ثم redirect لصفحة الكورس

---

## 5) تدفق دفع اشتراك المعلم عبر فواتيرك

### 5.1 `prepare` للاشتراك

نداء: `POST /pricing/checkout/{plan}/fawaterak/prepare`

داخل `SubscriptionCheckoutController::fawaterakPrepare`:

- ينشئ/يحدّث `SubscriptionRequest` بحالة `pending` وبـ:
  - `payment_method=online`
  - `payment_proof=null`
- يحفظ `fawaterak_subscription_request_id` في session (ويمسح `fawaterak_order_id`)
- يعيد `mode=iframe` أو `mode=api` بنفس الفكرة

### 5.2 العودة (success/fail/pending) للاشتراك

لا يوجد Route منفصل للاشتراك؛ نفس:

- `GET /checkout/fawaterak/{status}`

داخل `CheckoutController::fawaterakReturn`:

- إذا وجد `fawaterak_subscription_request_id` في session → يذهب إلى `fawaterakSubscriptionReturn`
- عند `success` يتم التفعيل عبر:
  - `TeacherSubscriptionActivationService::activateAfterGatewayPayment(...)`

---

## 6) ماذا يُخزَّن في قاعدة البيانات؟

### 6.1 عند وضع API (تخزين invoice الخارجي)

- `orders.fawaterak_invoice_id` (مضاف في migration: `2026_04_12_120000_add_fawaterak_invoice_id_to_orders_table.php`)
- `subscription_requests.fawaterak_invoice_id` (مضاف في migration: `2026_04_16_120000_add_gateway_fee_columns_and_subscription_fawaterak.php`)

### 6.2 عند نجاح الاعتماد (إنشاء الفوترة المحلية)

يتم إنشاء/تحديث:

- `invoices`: `status=paid` + `paid_at`
- `payments`: `status=completed`
  - `gateway_fee_amount`
  - `net_after_gateway_fee`
  - `gateway_response` يحتوي بيانات callback/query إن وجدت
- `transactions`:
  - قيد `credit` للمبلغ
  - قيد `fee` (debit) لعمولة البوابة إن كانت > 0

---

## 7) لماذا يوجد بروكسي سكربت الإضافة؟

`FawaterkPluginController` يجلب سكربت فواتيرك upstream ثم يقدمه من نفس الدومين مع كاش.

الأهداف:

- تقليل مشاكل CSP / بعض المتصفحات / إضافات الحجب
- ضمان `Content-Type` صحيح
- منع تقديم HTML بدل JS (لو فشل upstream)

---

## 8) مشاكل شائعة وحلولها (حسب الربط الحالي)

- **Invalid Token / inactive vendor**:
  - `inactive vendor`: مشكلة تفعيل عند فواتيرك
  - `Invalid Token`: غالباً خلط مفاتيح test/live أو `FAWATERAK_PLUGIN_BEARER_TOKEN` غير صحيح
- **فشل HMAC (iframe)**:
  - اضبط `FAWATERAK_IFRAME_DOMAIN` ليطابق الدومين المسجل لديهم (وغالباً بدون `/` أخيرة)
- **419/401 في prepare**:
  - 419: CSRF انتهى → F5
  - 401: جلسة الدخول انتهت → تسجيل الدخول مجدداً
- **انتهت جلسة الدفع بعد نجاح الدفع**:
  - فقد session بسبب اختلاف الدومين/HTTPS/SESSION_DOMAIN/SameSite

---

## 9) حدود الربط الحالي (مهم)

- الاعتماد النهائي يعتمد على **عودة المتصفح + session**. إذا اكتمل الدفع خارجياً ولم تعد الجلسة، قد لا يتم الاعتماد تلقائياً.
- checkout للمسار التعليمي (Learning Path) حالياً لا يستخدم فواتيرك (مُعطل في `showLearningPath`).

---

*آخر تحديث: أيار 2026 — مطابق للكود الحالي في حصتك (iframe + api للكورس والاشتراكات).*

