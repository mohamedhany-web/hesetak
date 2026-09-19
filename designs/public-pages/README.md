# تصميمات الصفحات العامة — حصتك (MyCourses)

مجلد **مرآة** لصفحات اللاندنج الإنتاجية.  
**مصدر الحقيقة للمنتج/CX:** `.cursor/skills/hesetak-public-pages` + `docs/hesetak/`.

**قاعدة القواعد:** `.cursor/rules/coding-architecture.mdc` + `blade-public-ui.mdc`  
- إعادة استخدام `mc-*` فقط  
- لا علامة سنا / تدريس لاب / Lasles كمنتج  
- الجمهور: طالب / ولي أمر (+ تقديم معلم منفصل)

## الصفحات الحية (Blade)

| المسار | Blade |
|--------|--------|
| `/` | `welcome` + `partials/landing/mycourses/*` |
| `/for-students` `/for-teachers` `/how-it-works` `/curricula` `/faq` | `public/marketing/*` |
| `/about` `/contact` | `public/site/*` → `layouts/mycourses-public` |
| `/courses` `/pricing` `/instructors` | standalone + `mc` nav/footer |
| `/login` `/register` | `layouts/auth-landing` + نصوص `auth.*` لطالب/ولي |

## مرآة HTML

| ملف | الحالة |
|-----|--------|
| [01-home.html](01-home.html) | يُحدَّث ليطابق `landing.mc.*` |
| 02–10 | أرشيف/قديم — لا تعتمد عليه للـ CX؛ حدّث عند الحاجة من Blade الحي |

## الإنتاج

```
resources/views/partials/landing/mycourses/
resources/views/layouts/mycourses-public.blade.php
public/css/landing/mycourses.css
docs/hesetak/
```

`layouts/lasles-public` = **alias** لـ mycourses-public (بدون تحميل lasles.css).  
`partials/landing/navbar` و `footer` → mycourses.
