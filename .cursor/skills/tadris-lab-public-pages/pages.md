# Public pages — CX briefs (حصتك)

Use these as the **experience contract**. Visuals follow Lasles tokens; content and IA follow this file + `tadris-lab-platform`.

Rewrite lang keys when migrating. Do not keep حصتك/Sana school/language-student stories.

---

## Home (`01-home` → `welcome` + `partials/landing/lasles/*`)

**Job:** Make a teacher understand the platform in one scroll and start the loop.

| Section (Lasles shell) | CX meaning for TADRIS |
|------------------------|------------------------|
| Nav | Brand + About / Features / Pricing / Proof / Help + auth |
| Hero | Promise: diagnose classroom challenges → grow practice. CTA primary = start / diagnose or register |
| Stats | Teacher outcomes (e.g. practices used, challenges completed) — not VPN metrics |
| Features | Practices, tools, applied challenges, progress measurement |
| Pricing | Plans for teacher PD access (if offered) — not tutoring packages |
| Network / map | Optional: reach / schools / communities of practice — only if true |
| Testimonials | Teachers speaking about practice growth |
| Subscribe / CTA | Continue: account, contact, or first diagnostic |
| Footer | Product / Engage / legal — teacher wording |

---

## The Path / المسار (`10-path` → `/path`)

**Job:** Show the teacher the diagnose→access→develop→measure journey before signup.

- Nav label: المسار / The Path  
- Home teaser: `#path` section after stats  
- Full page: `public.path`  
- CTA: ابدأ المسار → register  

---

## About

**Job:** Why حصتك exists; trust in the professional-development mission.

- Story = teachers’ classroom reality → need for diagnosis + applied practice  
- Not: language academy origin story  
- CTA → home loop start or contact  

---

## Paths / catalog (legacy “courses”)

**Job:** Help the teacher find a **development path** (practices / tools / challenges), not a language course list.

- Labels: مسارات / وحدات تطوير / ممارسات — avoid «كورسات لغة»  
- Cards lead to path detail with clear next step in the loop  
- If inventory is still حصتك courses, REPURPOSE copy and hide misleading student CTAs (`tadris-lab-platform`)  

---

## Pricing

**Job:** Choose access level for professional growth tools.

- Plans map to access (diagnose / library / challenges / progress)  
- No free-trial language lesson framing  
- CTA → register / contact sales-light only if KEEP  

---

## Contact

**Job:** Teacher (or school lead) reaches support about PD use — not parent booking a trial.

- Form fields and help text assume معلم / مؤسسة تعليمي  
- FAQs point to loop steps  

---

## Auth login / register

**Job:** Enter or create a **teacher learner** account.

- Role language: معلم / حساب مهني — no student/parent switcher  
- Split layout: loop aside (diagnose→access→develop→measure) + focused form  
- Visual: `lasles-auth` + Rubik + `#F53838` (`public/css/landing/lasles-auth.css`)  
- Mirrors: `06-auth-login.html`, `07-auth-register.html`  
- After login destination should serve KEEP areas (dashboard aligned to PD), not student tutoring desk  

---

## Instructors / mentors (if KEEP)

**Job:** Show facilitators/content mentors who support teacher growth — not private language tutors for hire.

- If product disables tutor marketplace, hide public hiring CTAs  

---

## Tutor apply (usually DISABLE)

Default platform verdict: **DISABLE** hiring funnel for language tutors.  
Only redesign if user explicitly keeps a **content facilitator** apply flow; then rewrite CX accordingly.

---

## FAQ / Terms / Privacy / Help

**Job:** Reduce risk and explain PD product policies.

- Replace language-school policies with teacher-platform terms when editing  
- Keep legal accuracy; ask user before inventing legal claims  

---

## Shared shell rules

1. One primary CTA per viewport section.  
2. Nav labels match teacher jobs (عن المنصة، الميزات، الباقات، آراء المعلمين، مساعدة).  
3. Footer never advertises disabled حصتك modules.  
4. SEO title/description/keywords = حصتك + PD terms (`components/seo-meta`, `landing.meta`).  
