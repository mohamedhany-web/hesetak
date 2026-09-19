# حصتك — Visual system (public)

Source of truth in code after homepage redesign. Prefer these over older Lasles/Sana themes for new public work.

## Brand assets

| File | Use |
|------|-----|
| `public/img/brand/hesetak-mark.png` | Nav, drawer, footer, auth (via brand partial) |
| `public/img/brand/hesetak-logo.png` | Larger placements / OG if needed |
| `public/img/brand/hesetak-favicon-32.png` | Favicon fallback |
| `partials/landing/mycourses/brand.blade.php` | Always use this for wordmark + mark |

Brand string: `__('landing.nav.brand')` → **حصتك** / **Hesetak**.

## Color tokens (`mycourses.css` `:root`)

| Role | Token | Hex | Use |
|------|-------|-----|-----|
| Navy | `--mc-navy` / `--mc-primary` | `#1E4E8C` | Links, key UI, outlines |
| Navy deep | `--mc-navy-deep` / `--mc-ink` | `#152A4A` | Headings, text |
| Navy soft | `--mc-navy-soft` | `#E8EEF6` | Soft fills, active drawer links |
| Gold | `--mc-secondary` | `#C9952A` | **Booking / buy CTAs only** |
| Gold soft | `--mc-secondary-soft` | `#F7F1E8` | Soft gold fills |
| Surface | `--mc-surface` / `2` | `#FFF` / `#F4F6F9` | Page / muted sections |
| Line | `--mc-line` | `#E4E4E4` | Borders |

Do not invent purple gradients, cream-serif “AI defaults”, or teal as primary.

## Typography

- Shop/home: IBM Plex Sans Arabic + Lato + Rubik (`meras-shop.css`)
- Body: weights 400–500; titles 700–800; brand 800–900
- Line-height: regular ~1.5, bold titles ~1.3
- No em-dash `—` in Arabic marketing copy

## Shell (every public page)

```
topbar → sticky mc-nav → (page) → mc-footer → drawer JS
```

| Piece | Partial / notes |
|-------|-----------------|
| Topbar | `topbar.blade.php` — contact + WA; hide socials &lt;960 |
| Nav | `nav.blade.php` — brand · links (≥960) · actions |
| Actions (mobile) | Lang toggle **then** hamburger at the **inline-end edge** |
| Drawer | Opens from **inline-start** (يمين في RTL، يسار في LTR) |
| Footer | `footer.blade.php` — same brand partial pattern |
| Favicon | `partials/favicon-links` → brand assets |

Breakpoint: **960px** for desktop links vs drawer.

### Drawer UX

- Backdrop + Escape + link click close  
- `body.mc-drawer-open` locks scroll  
- Active link: navy soft + gold inset bar  
- Auth CTAs in drawer foot; lang stays in navbar  

## Homepage shop layer

Body: `mc-body mc-body--shop`  
CSS: `meras-shop.css` (loaded with home)

| Class | Role |
|-------|------|
| `.dp-hero` | Full-bleed hero, min-height via `--dp-hero-h` |
| `.dp-mosaic` | 3 path cards (1 col → 3 from 768) |
| `.mc-teachers--dir` / `.mc-teacher--sm` | Compact teacher grid |
| `.mc-packages` | 1 col mobile → 3 from 768; no scale on mobile recommended card |

Hero budget: brand signal + one headline + one lead + CTA group + full-bleed media. No stats strip / floating chips on media.

## Component patterns to reuse

- Buttons: `mc-btn` + `mc-btn--secondary` (gold book) / `mc-btn--primary` (navy) / `mc-btn--soft` / `mc-btn--ghost-on-dark`
- Section head: eyebrow + title + lead + optional `mc-link-more`
- Filters (instructors): compact `mc-dir-bar` — not huge filter walls
- Cards: use only when they carry an action (book, buy, open path). Prefer mosaic/path tiles over empty chrome cards

## Motion

Prefer 2–3 intentional transitions (drawer slide, hover lift on mosaic/teacher). Respect `prefers-reduced-motion`.

## Anti-patterns

- Mixing Lasles teal or Sana themes on new حصتك pages  
- Letter-mark «ح» instead of logo image  
- Hamburger next to brand on the start side (keep it at the edge with lang)  
- Drawer from the wrong side in RTL  
- Hardcoded «حصتك» in EN locale  
