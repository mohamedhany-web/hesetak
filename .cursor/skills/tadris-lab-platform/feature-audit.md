---
# Feature audit catalogue — inherited حصتك → حصتك

Starter verdicts are **hypotheses** from the product brief. Confirm with the user before bulk disable. Update this file when verdicts are finalized.

## How to re-scan the codebase

1. Public + auth routes: `routes/web.php`
2. Admin nav: `resources/views/layouts/admin-sidebar.blade.php` + `config/admin_sidebar_role_map.php`
3. Panels: `resources/views/{admin,student,instructor,employee,public,tutor}/`
4. Models: `app/Models/`
5. Controllers: `app/Http/Controllers/{Admin,Student,Instructor,Employee,Public,Community}/`

## Domain catalogue

| Domain | Typical paths / signals | Starter verdict | Notes for حصتك |
|--------|-------------------------|-----------------|----------------------|
| Auth / users / roles / permissions | `Auth`, `Role`, `Permission`, admin users | **KEEP** | أساس المنصة |
| System settings / activity log / notifications | `Setting`, admin system-settings, notifications | **KEEP** | تشغيل عام |
| Media / secure file / storage | `StorageFileController`, media gallery | **KEEP** | بنية تحتية |
| Public marketing shell (about, contact, FAQ, terms, privacy) | `public.about`, contact, FAQ | **REPURPOSE** | أبقِ الهيكل؛ غيّر النص والعلامة لحصتك |
| Courses / lessons / enrollments / packages | `Course`, `public.courses`, student learn | **REPURPOSE** | قد تصبح مسارات/وحدات تطوير مهني — ليس دروس لغة للطلاب |
| Exams / question bank / assignments | `Exam`, `QuestionBank`, assignments | **REPURPOSE** | مفيد للتشخيص وقياس التقدّم إن أُعيد التوجيه |
| Certificates / badges / achievements / XP | certificates, badges, streaks | **REPURPOSE** | قياس تقدّم المعلم — لا شهادات طالب لغة كما هي |
| Curriculum library / libraries / materials | curriculum-library, libraries | **REPURPOSE** | مكتبة ممارسات/أدوات محتملة |
| Classroom live room / LiveKit / live sessions / recordings | classroom join, live-sessions, LiveKit | **DISABLE** أو **REPURPOSE** بعد قرار | بث حصص خصوصي إرث؛ عطّل للواجهة حتى يتحدد إن كان لحصتك حاجة لغرف تطبيقية |
| Tutoring groups / cohorts / bookings / subscriptions | tutoring-*, `/groups`, one-to-one | **DISABLE** | قلب نموذج حصتك للطلاب |
| Free trial booking | free-trial | **DISABLE** | تسويق حصص تجريبية للغات |
| Parent progress | `public.parent-progress` | **DISABLE** | ولي أمر — خارج النطاق |
| Student panel (`role:student`) | `student.*` routes/views | **DISABLE** أو إعادة تسمية لاحقة لدور المعلم-المتعلّم | لا تبنِ UX «طالب» ظاهرًا |
| Instructor panel as language tutor | instructor tutoring, work schedule, withdrawals | **REPURPOSE**/**DISABLE** جزئي | قد يبقى «ميسّر/مدرب محتوى»؛ عطّل جداول الحصص الخصوصية والسحب إن لم تُطلب |
| Tutor apply / hiring / HR recruitment | tutor/apply, hiring-form, Hr* | **DISABLE** | توظيف مدرسي لغة |
| CRM / sales leads / commissions | `admin.crm.*`, sales | **DISABLE** | مبيعات أكاديمية لغات — إلا إذا طُلب CRM B2B لاحقًا |
| Service packages / custom quotes checkout | service-packages | **DISABLE** | عروض خدمات الإرث |
| Wallets / installments / instructor salaries / payouts | wallets, installments, salaries | **DISABLE** (مالي الإرث) | أبقِ فواتير/مدفوعات عامة فقط إن لزم اشتراكات لاحقًا — أكّد مع المستخدم |
| Payment gateways (Fawaterak/PayPal/Kashier) | webhooks, checkout | **KEEP** بنية؛ **DISABLE** مسارات الشراء غير ذات الصلة | لا تكسر الويب هوكس؛ أخفِ checkout غير المستخدم |
| Coupons / loyalty / referrals | coupons, loyalty, referrals | **DISABLE** | نمو مبيعات الإرث |
| Consultations (student↔instructor) | consultations | **DISABLE** أو **REPURPOSE** | استشارات صفية للمعلم قد تُعاد لاحقًا |
| Community competitions / datasets / models | Community* | **DISABLE** | مجتمع ML/مسابقات الإرث إن وُجد |
| Employee desk / HR / salaries ops | employee/*, Hr* | **DISABLE** أو إبقاء إداري خفيف | أكّد احتياج تشغيل حصتك |
| Arabic alphabet / school games | `arabic_alphabet_game`, `school_game` | **DISABLE** | ألعاب متعلّم لغة |
| WhatsApp / n8n live reports ops | WhatsApp, n8n | **DISABLE** حتى يُطلب للتشغيل | تكاملات تشغيل حصتك |
| Popup ads / marketing widgets | PopupAd, marketing | **DISABLE** | إلا حملات حصتك لاحقًا |
| Placement tests (language) | placement | **DISABLE** أو **REPURPOSE** لتشخيص ممارسة صفية | لا تتركه كـ placement لغة |
| Academic years / subjects / schools (K-12 tutoring structure) | AcademicYear, School, Subject | **REPURPOSE**/**DISABLE** | قد تُستخدم لسياق المعلم المدرسي — لا تفترض نموذج الطالب |
| Support tickets | support | **KEEP** خفيف | دعم مستخدمي المنصة |
| Site services / testimonials / events / partners | site services, testimonials | **REPURPOSE** | محتوى تسويقي يُعاد كتابته |

## Likely first DISABLE batch (UI + routes)

استخدم هذه القائمة كاقتراح أول نقاش مع المستخدم:

1. Free trial + parent progress  
2. Tutoring groups / one-to-one / tutoring subscriptions / bookings  
3. Public `/groups`, service-packages checkout flows  
4. CRM + sales desk (+ employee sales)  
5. Tutor apply + hiring hub (إن لم يُعرَّف توظيف لميسّري حصتك)  
6. Student-facing gamification/language games configs  
7. Community competitions (إن ظهرت في السايدبار)  
8. Live tutoring sessions nav (مع الإبقاء على الكود)

## Disable implementation notes (Laravel)

Preferred patterns in this repo:

- Sidebar: wrap blocks in `@if(config('platform.modules.X.enabled'))` after adding keys to `config/platform.php`
- Routes: group surplus routes under the same config check or a small middleware `EnsureModuleEnabled:X`
- Public CTAs in Blade layouts/partials: hide with the same config
- Do **not** drop tables or delete `app/Models/*` in the first pass
- After disable, smoke-check: `/`, login, admin dashboard still load

### Suggested `config/platform.php` module keys

```php
'modules' => [
    'tutoring' => ['enabled' => false],
    'free_trial' => ['enabled' => false],
    'parent_progress' => ['enabled' => false],
    'crm_sales' => ['enabled' => false],
    'tutor_hiring' => ['enabled' => false],
    'live_classroom' => ['enabled' => false],
    'community' => ['enabled' => false],
    'loyalty_referrals' => ['enabled' => false],
    'language_games' => ['enabled' => false],
    'student_panel' => ['enabled' => false],
    // KEEP-ish defaults
    'courses' => ['enabled' => true],      // pending REPURPOSE
    'assessments' => ['enabled' => true],  // pending REPURPOSE
    'certificates' => ['enabled' => true], // pending REPURPOSE
    'payments' => ['enabled' => true],
],
```

Only add keys you actually gate; expand as disables land.

## Finalized decisions log

| Date | Domain | Verdict | Applied? | Notes |
|------|--------|---------|----------|-------|
| | | | | |
