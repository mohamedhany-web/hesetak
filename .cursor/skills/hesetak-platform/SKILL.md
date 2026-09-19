---
name: hesetak-platform
description: >-
  Source of truth for حصتك (Hesetak) product scope from الدليل الشامل الموحّد v3.0
  (إيكونكس). Defines business model (online 1:1 tutoring + curricula + independent
  courses), roles, journeys, finance/CRM, AI rules, and what is EXCLUDED as مرجعي.
  Use when scoping features, roles, sidebar, routes, roadmap, naming, or when the
  user mentions حصتك, دروس خصوصية, مناهج, كورسات, ولي أمر, محفظة, توظيف معلمين,
  or ما نبنيه / ما مش هنحتاجه.
---

# حصتك — Platform Scope (v3.0)

**Source:** `حصتك - الدليل الشامل الموحّد v3.0 [إيكونك360].pdf` — ملكية إيكونكس.  
**Brand / geography / mission (client):** `docs/hesetak/00-brand-voice.md` — **يلغي أي ادّعاء حضور جغرافي مخالف.**  
This skill is the **product source of truth**. Older `tadris-lab-*` skills that define «تطوير مهني للمعلمين فقط» are **superseded** for product scope.

## Product definition (memorize)

**حصتك** = منصة تعليمية دولية متخصصة في **الدروس الخصوصية أونلاين**، مع:

1. **حجز دروس خصوصية مباشرة أونلاين (فردي 1:1)** — الموديل الأساسي  
2. **مسار المناهج الدراسية الرسمية** — مطابقة حسب *نوع المنهج* (من الدليل؛ سعودي الأشهر) — **ليس** ادّعاء فروع بكل دولة  
3. **مسار الكورسات المستقلة** — تحضير اختبارات، مهارات، تحفيظ قرآن، لغات، تطوير مهني، دورات لأولياء الأمور  

**حضور معلن (براند):** الولايات المتحدة · مصر · السعودية — فقط (`00-brand-voice.md`).  
**رؤية البراند:** الأولى والأكثر موثوقية للطلاب في دول الخليج ومصر (طموح، لا قائمة حضور).  
**المعلمون:** معتمدون؛ إمكانية اختيار/تغيير المعلم بسهولة.  

**لا تلفّق جغرافيا أو قصة إطلاق.** إن تعارض الدليل مع `00-brand-voice.md` في الحضور التسويقي → اتبع ملف البراند.

## Badge rules (critical)

| شارة | المعنى | في البناء |
|------|--------|----------|
| **AI** | ذكاء اصطناعي = أداة دعم قرار، ليس بديلاً عن مراجعة بشرية حيث تكون حساسة | اسمح مع قاعدة «اقتراح → مراجعة بشرية» |
| **جديد** | من المقارنة ويخدم موديل الأونلاين الفردي مباشرة | ضمن النطاق |
| **مرجعي** | فروع فعلية / فصول جماعية / HR موظفين / مخزون… | **لا تبنِ · لا تجدول · لا تقترح في الـ UX** |

**الجزء الثامن من الدليل = مرجعي بالكامل — تجاهله إلا بقرار صريح من صاحب القرار.**

## Design / CX one-liners per dashboard

| دور | السؤال الفوري على الشاشة |
|-----|---------------------------|
| طالب | إيه اللي جاي عليّ؟ |
| معلم | هل أنا شغال صح وهدخل فلوسي إمتى؟ |
| إدارة | إيه اللي يحتاج تدخّل الآن؟ |

Visual identity (الدليل): كحلي أساسي + ذهبي/برتقالي للحجز والشراء + أبيض/رمادي داكن. RTL أصالةً. جوال أولاً للطلاب وأولياء الأمور.  
Public UI code may currently use MyCourses `mc-*` tokens — map brand copy/CX to this product; do not invent «teacher-PD-only» marketing.

## Core loops (in scope)

### Student / parent
تصفح → اختيار مسار (فردي / منهج / كورس) → مطابقة معلم (مادة+مرحلة+نوع منهج) → حجز بتوقيت محلي → حصة → تقييم إلزامي + تقرير معلم → محفظة/فواتير → شهادات.

### Teacher
تقديم → فرز/مراجعة → مقابلة تقنية → اعتماد → تأهيل إلزامي → توافر → حصص → تقرير إلزامي قبل الأجر → محفظة مستحقات.

### Platform money
الطالب يدفع مقدمًا (باقة/محفظة) → المنصة تحوّل للمعلم شهريًا بعد خصم العمولة → كل حركة سجل دائم غير قابل للتعديل المباشر.

## Roles in scope (build these)

زائر · طالب/ولي أمر · ولي أمر (ربط أبناء) · معلم قيد المراجعة · معلم معتمد · محاسب مالي · فريق توظيف · فريق علاقات عملاء · مشرف أكاديمي (جودة) · مدير عام.

**مرجعي — لا تفعّل:** مدير فرع وأدوار مراكز فعلية.

## Hard product rules

1. صلاحية تُتحقق عند التنفيذ، لا عند إخفاء الزر فقط.  
2. تبديل المعلم بلا تكلفة/قيود على رصيد المحفظة.  
3. الحصة لا تُعتبر مكتملة ماليًا إلا بعد تقرير المعلم الإلزامي.  
4. مطابقة المعلم ثلاثية: مرحلة + مادة + نوع منهج.  
5. تأكيد الدفع النهائي من webhook البوابة فقط — ليس من redirect المتصفح.  
6. AI outputs تبدأ `pending_review` — لا تظهر للمستخدم النهائي قبل موافقة بشرية حيث ينص الدليل.  
7. لا تنفّذ ميزات الجزء الثامن (فروع، HR، حضور جماعي، أنشطة لامنهجية، مخزون، ويكي داخلي).

## Progressive disclosure

Load only what the task needs:

| File | When |
|------|------|
| [exclude-reference.md](exclude-reference.md) | أي نقاش «هل نضيف فروع/جماعي/HR؟» |
| [roles-journeys.md](roles-journeys.md) | أدوار، رحلات طالب/ولي/معلم، صلاحيات |
| [content-model.md](content-model.md) | مناهج، كورسات، اختبارات، LMS |
| [operations.md](operations.md) | مالية، فواتير، CRM، توظيف، جودة، دردشة، شهادات، إشعارات، مدفوعات، سياسات |
| [ai-roadmap-data.md](ai-roadmap-data.md) | معماري AI، قرارات معلّقة، جداول بيانات، خريطة موقع، خارطة طريق |
| [product-definition.md](product-definition.md) | تعريف موسّع + هوية بصرية ومكوّنات UI من الدليل |

## Audit workflow

```
Audit:
- [ ] 1. Is it in-scope for online 1:1 + curricula + independent courses?
- [ ] 2. Marked مرجعي in الدليل? → STOP / do not build
- [ ] 3. AI? → proposal + human review path required
- [ ] 4. Touches money/chat/minors? → follow operations.md policies
- [ ] 5. Propose KEEP | REPURPOSE | DISABLE (disable inherited Glottical surplus; do not mass-delete)
```

## Working docs (human + agent)

ملفات عمل مفصّلة للتنفيذ في المستودع:

| Path | Content |
|------|---------|
| `docs/hesetak/README.md` | فهرس وثائق حصتك |
| `docs/hesetak/01-product-overview.md` | ملخص المنتج اليومي |
| `docs/hesetak/02-competitors-teacherk-moalimy.md` | منافسون |
| `docs/hesetak/03-public-ia-sitemap.md` | IA صفحات عامة |
| `docs/hesetak/04-page-briefs.md` | موجزات صفحات |
| `docs/hesetak/05-gaps-and-priorities.md` | أولويات مقابل الكود |

## Relationship to other skills

| Skill | Role |
|-------|------|
| **`hesetak-platform`** | What the product is / is not |
| `hesetak-public-pages` | How public CX+UI must be built |
| `tadris-lab-*` | **Deprecated for product scope** — redirect here |
