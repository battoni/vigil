---
version: 3.0.0
origin: vigil
based-on: 2.0.0
---

# Theme System

A **multi-theme**, **light/dark** token system. One active brand theme is chosen at
build time; the user toggles light/dark at runtime. Components consume **role tokens**
(structure) and **state palettes** (meaning) — never raw brand ramps — so they retheme
and flip schemes with zero per-component edits.

---

## Layout

```text
styles/theme/
  colors.css            ← ACTIVE-THEME SELECTOR: a single @import of one themes/*.css
  fonts.css             ← @font-face declarations (WOFF2 in public/fonts/)
  themes/
    battoni-dev.css     ← the default theme (palette ramps + role tokens + dark overrides)
    _template.css       ← copy this to start a new theme
```

## Three token tiers

1. **Brand palette ramps** — `primary-*`, `ink-*`, `surface-*` (50→950). Per theme. Fixed
   light→dark scales; they do **not** flip by scheme. Raw material only.
2. **Role tokens** — the layer components use for **structure**. They flip per scheme:

   | Token / utility                       | Use for                         |
   | ------------------------------------- | ------------------------------- |
   | `canvas` (`bg-canvas`)                | page background                 |
   | `panel` (`bg-panel`)                  | cards, panels, raised surfaces  |
   | `panel-muted` (`bg-panel-muted`)      | hover / subtle fills            |
   | `heading` (`text-heading`)            | titles, strong headings         |
   | `body` (`text-body`)                  | default body text               |
   | `muted` (`text-muted`)                | labels, secondary text          |
   | `subtle` (`text-subtle`)              | faint text, icons, placeholders |
   | `line` (`border-line`, `divide-line`) | default borders / dividers      |
   | `line-strong` (`border-line-strong`)  | emphasized borders              |

3. **State palettes** — `success`, `info`, `warn`, `danger`, `help`, `contrast` (50→950).
   Brand-agnostic, identical across themes, same in light and dark. Use for status/meaning.

**Brand accents** (`primary-*`) stay literal — green reads well in both schemes.

---

## How light/dark works

- Role tokens are declared in each theme's `@theme` block with **light** values, then
  overridden under `[data-theme='dark']`. They're CSS custom properties, so the same
  utility (`bg-panel`) resolves to the dark value automatically — **no `dark:` variants**.
- The scheme is set on `<html data-theme="light|dark">` by `colorScheme.store.ts`
  (persisted to `localStorage`, defaults to system). An inline script in `index.html`
  sets it before paint to avoid a flash.
- PrimeVue: `darkModeSelector: '[data-theme="dark"]'`; the preset (`libraries/primevue/theme.ts`)
  defines `semantic.colorScheme.dark` (an inverted surface scale) and `dark` blocks per
  component. Component overrides reference tokens, so most dark blocks mirror light.

---

## Contributor conventions

- **Structure → role tokens.** Backgrounds (`bg-canvas`/`bg-panel`), text
  (`text-heading`/`text-body`/`text-muted`/`text-subtle`), borders
  (`border-line`/`border-line-strong`). These flip for dark.
- **Meaning → state palettes.** `bg-success-100`, `text-danger-700`, etc.
- **Brand action → `primary-*`.**
- **Never** use raw `surface-*` / `ink-*` shades in components — they don't flip; use the
  role token that maps to them.
- **Never** use `bg-white` / `text-black` / hex literals — use `bg-canvas` / role tokens.
- **Never** use Tailwind built-in palettes (`gray`, `sky`, `emerald`, `red`, `slate`, …).

---

## Forking / adding a theme

1. Copy `themes/_template.css` → `themes/<your-theme>.css`.
2. Fill the `primary` / `ink` / `surface` ramps with your brand scales. Copy the state
   palettes verbatim (change only if the brand mandates different state colors).
3. Role-token light/dark mappings usually need no change.
4. Fonts differ? Update `--font-sans`/`--font-mono` and add `@font-face` in `fonts.css`
   (+ WOFF2 in `public/fonts/`).
5. Point `colors.css` at your file: `@import './themes/<your-theme>.css';`
6. Update `ACTIVE_THEME` in `src/constants/theme.constant.ts` (used for non-CSS concerns).
7. Restart dev / clear `node_modules/.vite` (Vite caches the `@import` graph), then verify light + dark.

**No component files change** — they only use role tokens and state palettes.
