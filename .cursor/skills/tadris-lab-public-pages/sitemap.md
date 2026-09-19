# Public IA — expandable sitemap

Source of truth: `config/tadris_nav.php` + `lang/{ar,en}/site.php`

To add a page:
1. Add node (and optional children) in `config/tadris_nav.php`
2. Add `site.nav.{key}` + `site.pages.{key}` in ar/en
3. Route auto-registers when `route` starts with `public.site.`

## Top nav (priority hubs)

- Home
- About
- Teacher Development (+ paths / courses / resources)
- Educational Consultations (+ teachers / specialized) → `/educational-consultations`
- Institutional Development (+ schools / training / solutions)
- Assessment & Diagnosis (+ diagnosis / recommendations)
- Contact

Also in full mobile nav / footer: Workshops, Resources library, Account & progress, Certificates.

## CX

Every page uses Lasles shell + teacher/institution framing (not حصتك student tutoring).
