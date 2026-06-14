# Design System

This page is the source of truth for app.vigil UI styling decisions.

It documents the design rules implemented in:

- `app.vigil/src/styles/theme/colors.css`
- `app.vigil/src/libraries/primevue/theme.ts`
- `app.vigil/src/styles/libraries/primevue.css`

Use this page before adding new styles or tokens.

## 1) Foundations

### 1.1 Font families

- **Primary UI font**: `Space Grotesk`
- **Monospace/supporting text font**: `Sometype Mono`

Usage:

- Headings, labels, buttons, menu text: `Space Grotesk`
- Paragraph-like body copy and code-like content: `Sometype Mono`

### 1.2 Radius scale

Token scale:

- `none`: `0`
- `xs`: `2px`
- `sm`: `4px`
- `md`: `6px`
- `lg`: `8px`
- `xl`: `10px`

Component defaults currently used:

- Form fields (`InputText`, `Select`, `Textarea`, etc.): `md` (`6px`)
- Buttons default: `md` (`6px`)
- Buttons with `rounded`: `lg` (`8px`) (not full pill)
- Card root: `lg` (`8px`)
- Content/overlay (select/popover): `md` (`6px`)

### 1.3 Focus ring

- Width: `1px`
- Style: `solid`
- Color: `primary.500`
- Offset: `0` for form fields, `2px` global default

---

## 2) Color system

app.vigil is **multi-theme** (one active brand theme chosen at build time) and **light/dark**
(toggled at runtime). Components consume **role tokens** and **state palettes** — never raw
brand ramps. See `app.vigil/src/styles/theme/RULES.md` for the full system.

### 2.1 Brand palette ramps (raw material)

Per-theme fixed scales; they do **not** flip by scheme:

- **Primary**: action and emphasis color (`--color-primary-*`, main `#c0e021`)
- **Ink**: dark text scale (`--color-ink-*`, main `#141413`)
- **Surface**: neutral scale (`--color-surface-*`, main `#888b8d`)

Don't use these shades directly in components — use the role tokens below.

### 2.2 Role tokens (structure — flip per scheme)

| Token / utility | Use for |
| --- | --- |
| `bg-canvas` | page background |
| `bg-panel` | cards, panels, raised surfaces |
| `bg-panel-muted` | hover / subtle fills |
| `text-heading` | titles, strong headings |
| `text-body` | default body text |
| `text-muted` | labels, secondary text |
| `text-subtle` | faint text, icons, placeholders |
| `border-line`, `divide-line` | default borders / dividers |
| `border-line-strong` | emphasized borders |

These are CSS custom properties overridden under `[data-theme='dark']`, so the same utility
resolves to the dark value — no `dark:` variants needed.

### 2.3 Semantic state palettes (meaning — same in both schemes)

Use semantic scales for state-driven UI:

- `success` (`--color-success-*`)
- `info` (`--color-info-*`)
- `warn` (`--color-warn-*`)
- `danger` (`--color-danger-*`)
- `help` (`--color-help-*`)
- `contrast` (`--color-contrast-*`)

### 2.4 Text role mapping

- **Primary title/header text**: `text-heading`
- **Default body text**: `text-body`
- **Form labels / secondary labels**: `text-muted`
- **Muted/supporting text**: `text-muted` to `text-subtle`
- **Error text**: `text-danger-700`

---

## 3) Typography rules

### 3.1 Form labels

Standard field label style:

- `text-sm`
- `text-muted`
- `mb-2 block`

Error state:

- Keep same structure and switch color to `text-danger-700`.

### 3.2 Dialog/form titles

Dialog titles use `text-heading` (strong, scheme-aware); labels below keep the muted hierarchy using `text-muted`.

---

## 4) Component specs

## 4.1 Buttons

Default shape and behavior:

- Radius: `6px` (`md`)
- Primary uses `primary.color` with `primary.contrast.color`
- Secondary uses `surface.100/200/300` for resting/hover/active

Rules:

- Do not add ad-hoc color classes for base semantic intent if tokenized severity is available.
- Prefer `severity="primary|secondary|success|info|warn|danger"` before custom utility overrides.

## 4.2 Inputs and fields

Field tokens:

- Horizontal padding: `0.75rem`
- Vertical padding: `0.5rem`
- Border radius: `6px`
- Border color baseline: `surface.300`

Applies to:

- `InputText`
- `Textarea`
- `Select`
- `MultiSelect`
- `Password`
- Other `form.field`-based PrimeVue controls

## 4.3 DataTable chrome

Global DataTable border rules:

- Header and cell border color: `surface.200`
- Header bottom border emphasis: `surface.300`

These are enforced in `primevue.css` to keep neutral table lines across views.

## 4.4 Cards

- Card radius: `8px` (`lg`)
- Use subtle surface background + neutral border (`surface.50` + `surface.300`) for standard metric/info cards.

---

## 5) Spacing rules

Use tokenized spacing utilities and existing component spacing before introducing new values.

Common field block spacing pattern:

- Field wrapper: `flex flex-col gap-2`
- Label margin: `mb-2`
- Form vertical rhythm: `gap-4`

---

## 6) Implementation snippets

### 6.1 Standard label + input

```vue
<div class="flex flex-col gap-2">
  <label class="mb-2 block text-sm text-muted" for="firstName">First Name</label>
  <InputText id="firstName" name="firstName" placeholder="Enter first name" />
</div>
```

### 6.2 Label with validation

```vue
<label :class="['mb-2 block', fieldError ? 'text-danger-700' : 'text-muted']">
  {{ $t('form.fieldName') }}
</label>
```

---

## 7) Do / Don't

Do:

- Use semantic tokens and utility classes mapped to the token system.
- Keep label color consistent with `text-muted` unless error.
- Reuse existing radius scale (`xs`..`xl`) and component defaults.

Don't:

- Hardcode one-off hex colors in components.
- Re-introduce circular semantic token references such as `semantic.primary.500 -> {primary.500}`.
- Use ad-hoc per-screen overrides when a theme token can solve it globally.

---

## 8) Change management

When updating design rules:

1. Update tokens in `app.vigil/src/styles/theme/colors.css` and/or `app.vigil/src/libraries/primevue/theme.ts`.
2. Add any required global structural overrides in `app.vigil/src/styles/libraries/primevue.css`.
3. Update this page in `codelumen/design-system.md`.
4. Validate with `npm run lint` and `npm run type-check` in `app.vigil`.

