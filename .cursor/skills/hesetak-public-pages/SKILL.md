---
name: hesetak-public-pages
description: >-
  Mandatory CX+UI for حصتك public/marketing pages. Canonical visual system from
  the live homepage (mc-* navy+gold, shop hero, side drawer RTL-from-right, brand
  mark). Per-page experiences for students/parents (and secondary teacher apply).
  Use for home, instructors, curricula, courses, pricing, contact, auth, FAQ,
  terms, landing CSS/Blade, designs/public-pages, or when the user mentions
  صفحات خارجية, لاندنج, حجز حصة, مناهج, دليل معلمين, ريبونسيف, منيو جانبي.
---

# حصتك — Public Pages (CX + UI)

**Load product truth first:** `.cursor/skills/hesetak-platform/SKILL.md`  
**Brand / geography / mission (client — do not invent):** `docs/hesetak/00-brand-voice.md`  
**Visual details:** [visual-system.md](visual-system.md)  
**Per-page target experience:** [page-experience.md](page-experience.md)

## Geography rule (non-negotiable)

- **حضور معلن في الواجهة:** أمريكا · مصر · السعودية فقط.  
- **الرؤية:** الخليج + مصر = طموح، لا تُعرض كقائمة فروع قائمة.  
- أنواع المناهج (سعودي/إماراتي/…) إن وُجدت = **فلتر نوع منهج** من الدليل، مع صياغة «منهج …» وليس «حصتك في الإمارات/الكويت».  
- أي نص عميل جديد يُحفظ في `00-brand-voice.md` قبل توسيع الادّعاءات.

## Golden rule

Every public page must feel like the **same product** as `/` (homepage): same brand, same shell, same trust language — but the **job of the page** and **who it talks to** change. Do not copy homepage sections blindly; design the journey for that page’s target.

## Primary target (default)

| Audience | Need | Tone |
|----------|------|------|
| **ولي أمر** | معلم موثوق، منهج واضح، تقرير بعد الحصة، حجز سهل | طمأنة + وضوح + إثبات |
| **طالب** | حصة فردية / كورس / منهج، مواعيد مرنة | مباشر، CTA قصير |

Secondary (only on `/for-teachers`, tutor apply): معلم يريد الاعتماد والدخل — لا تجعل هوية الموقع كله «تطوير مهني للمعلمين».

## Before coding any public page

Complete this brief (or load [page-experience.md](page-experience.md)):

1. **من الزائر؟** ولي أمر / طالب / معلم محتمل / مرحلة / دولة منهج  
2. **ماذا يريد في هذه الصفحة فقط؟**  
3. **CTA أساسي واحد** (احجز · ابحث عن معلم · اختر باقة · سجّل · قدّم كمعلم)  
4. **إثبات ثقة** (معتمد، تقييم، منهج خليجي، شهادة ولي أمر)  
5. **الخطوة التالية** في الحلقة (تصفح → ملف → حجز → حساب)  
6. **موبايل أولاً** — drawer من اليمين في RTL، توجل لغة في النافبار  

## Visual system (non-negotiable)

| Item | Source of truth |
|------|-----------------|
| Colors | Navy `#1E4E8C` / `#152A4A` + gold `#C9952A` for booking/buy only |
| CSS | `public/css/landing/mycourses.css` (`mc-*`) + shop layer `meras-shop.css` on home |
| Logo | `public/img/brand/hesetak-mark.png` via `partials/landing/mycourses/brand` |
| Brand name | `__('landing.nav.brand')` → حصتك / Hesetak |
| Shell | topbar + sticky nav + side drawer + footer (same partials) |
| Type | IBM Plex Sans Arabic + Lato/Rubik on shop; no Inter/Roboto as display |

Full tokens, nav/drawer, section patterns → [visual-system.md](visual-system.md)

## Homepage IA (canonical composition)

Reference implementation — change other pages *in the same language*, not by pasting all sections:

1. Full-bleed **shop hero** (`hero-shop`) — brand in title, one headline, one lead, CTA group  
2. **Promo mosaic** — 3 paths: 1:1 · مناهج · كورسات  
3. **Trending teachers** — compact cards + احجز حصة  
4. **Packages / best-selling** — clear hours + one buy CTA  
5. **Testimonials** — أولياء أمور  
6. Footer  

Partials: `resources/views/partials/landing/mycourses/*`  
Home body class: `mc-body mc-body--shop`

## Allowed CTAs

- ابحث عن معلم / احجز حصة  
- استكشف المناهج / الكورسات  
- اختر الباقة / اشترِ  
- انشاء حساب / تسجيل دخول  
- قدّم كمعلم (صفحات المعلمين فقط)  
- ابدأ اختبار مستوى (إن وُجد المسار)

## Forbidden

- مرجعي UX: فروع، فصول جماعية كمنتج أساسي، مخزون، HR  
- هوية teacher-PD فقط على الهوم  
- علامات قديمة: Glottical / Sana / MyCourse.io / تدريس لاب كبراند  
- شرطات طويلة `—` في النسخ العربية (فضّل «،» أو جملة جديدة)  
- إطار CSS جديد (Bootstrap/Tailwind UI kits) بدون موافقة  
- بطاقات زينة بلا فعل؛ hero مليان stats/chips/overlays  

## Responsive (required)

- Breakpoint nav/drawer: **960px**  
- RTL: drawer من **اليمين**؛ LTR: من اليسار  
- موبايل: هامبرغر على **طرف** الشريط + توجل لغة **جنبه**  
- لا overflow أفقي؛ باقة مميزة بلا `scale` على الموبايل  
- Touch targets ≥ ~44px للقائمة والـ CTAs  

## i18n

- كل النصوص في `lang/ar` + `lang/en` (`landing.*` / `public.*` / `site.*`)  
- مفتاح البراند: `landing.nav.brand`  
- لا تترك مفتاحًا ظاهرًا كنص (`landing.brand` خطأ — غير موجود)

## Working docs

| Doc | When |
|-----|------|
| `docs/hesetak/03-public-ia-sitemap.md` | IA |
| `docs/hesetak/04-page-briefs.md` | موجز صفحة |
| `docs/hesetak/02-competitors-teacherk-moalimy.md` | منافسون |
| [page-experience.md](page-experience.md) | تجربة التارجت لكل صفحة |
| [visual-system.md](visual-system.md) | توكنات وأنماط |

## Done checklist

- [ ] Brief التارجت مكتمل لهذه الصفحة  
- [ ] نفس الشِلّ (nav/drawer/footer/brand)  
- [ ] `mc-*` + كحلي/ذهبي + RTL  
- [ ] CTA واحد واضح يخدم الحلقة  
- [ ] موبايل: منيو من اليمين (AR) + توجل في النافبار  
- [ ] lang ar+en يعمل؛ البراند مترجم  
- [ ] لا مرجعي / لا براند قديم  
- [ ] طابق `docs/hesetak/04-page-briefs.md` عند بناء صفحة جديدة  
