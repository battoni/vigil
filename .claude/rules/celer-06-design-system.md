---
description: Design system rules – typography, spacing, radius, component patterns for app.vigil
globs: ["app.vigil/**/*.vue", "app.vigil/**/*.ts"]
alwaysApply: false
---
# Design System Rules

Canonical source: `codelumen/design-system.md`. Rules here are the enforcement-ready extract.

---

## Color tokens & scheme (CRITICAL)

app.vigil is **light/dark themed**. Components must use semantic tokens that flip per scheme — never raw brand ramps. Full reference: `app.vigil/src/styles/theme/RULES.md`.

- **Structure → role tokens** (these flip light↔dark automatically):

  | Token | Use for |
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

- **Meaning → state palettes:** `success` / `info` / `warn` / `danger` / `help` / `contrast` (e.g. `text-danger-700`, `bg-success-100`).
- **Brand action → `primary-*`.**

❌ **NEVER** use raw `surface-*` / `ink-*` shades, `bg-white` / `text-black`, hex literals, or Tailwind built-in palettes (`gray`, `sky`, `emerald`, `red`, `slate`, …) — none of these flip for dark mode.

✅ You almost never need `dark:` variants — role tokens handle the flip. Reserve `dark:` for genuine one-offs.

---

## Typography (CRITICAL)

### Form labels

Standard label:
```vue
<label class="mb-2 block text-sm text-muted" for="fieldId">
  {{ $t('key') }}
</label>
```

Error state — same structure, add color class:
```vue
<label :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']" for="fieldId">
```

❌ **NEVER:** `font-medium` or a different tier on a standard label — `text-muted` is the label color.

### Text role mapping

| Role | Class |
| --- | --- |
| Page/dialog titles, strong headings | `text-heading` |
| Default body text | `text-body` |
| Form labels, secondary labels | `text-muted` |
| Muted/supporting/hint text | `text-muted` to `text-subtle` |
| Error text | `text-danger-700` |

---

## Spacing (CRITICAL)

### Field block pattern

Always wrap a label + input pair like this:

```vue
<div class="flex flex-col gap-2">
  <label class="mb-2 block text-sm text-muted" for="id">...</label>
  <InputText id="id" ... />
</div>
```

- Field wrapper: `flex flex-col gap-2`
- Label bottom margin: `mb-2`
- Form vertical rhythm (between field blocks): `gap-4`

❌ **NEVER:** ad-hoc `mt-4`, `mb-6`, `py-3` on individual fields — use the gap on the parent form wrapper.

---

## Radius

Use only values from the project radius scale. Do not introduce arbitrary radius values.

| Token | Value | Tailwind class | Use for |
| --- | --- | --- | --- |
| `none` | `0` | `rounded-none` | — |
| `xs` | `2px` | `rounded-xs` | — |
| `sm` | `4px` | `rounded-sm` | Small chips, tags |
| `md` | `6px` | `rounded-md` | Form fields, buttons (PrimeVue default, no override needed) |
| `lg` | `8px` | `rounded-lg` | Cards, panels |
| `xl` | `10px` | `rounded-xl` | Modals, large containers |

❌ **NEVER:** `rounded-[6px]`, `rounded-[8px]`, `rounded-2xl`, `rounded-full` unless intentionally pill-shaped.

---

## Buttons (CRITICAL)

Prefer PrimeVue `severity` over custom color classes. PrimeVue's token system already handles background, border, hover, and active states for all severities — in both light and dark.

✅ **ALWAYS:**
```vue
<Button severity="primary" label="Save" />
<Button severity="secondary" label="Cancel" />
<Button severity="danger" label="Delete" />
```

❌ **NEVER:** add `bg-*`, `border-*`, `text-*` color classes to a `<Button>` to achieve a semantic intent that a severity already covers:
```vue
<!-- Wrong — severity="danger" does this for you -->
<Button class="bg-danger-500 text-white border-danger-500" label="Delete" />
```

Exception: layout-only classes (`w-full`, `mt-2`, `shadow-none`) are fine alongside severity.

---

## Component patterns

### Card chrome

Standard metric/info card:
- Background: `bg-panel`
- Border: `border border-line-strong`
- Radius: `rounded-xl` (matches PrimeVue Card `lg` = `8px` or full card `xl = 10px`)
- Shadow: `shadow-none` (design uses flat cards)

### DataTable chrome

Do not set border colors on DataTable cells via Tailwind — these are enforced globally in `primevue.css`. Only use Tailwind classes for layout (padding, height, overflow).

### Dialogs / modals

Title: `text-heading text-xl font-semibold text-center`

Use `MMainDialog` for all dialogs — do not use PrimeVue `<Dialog>` directly.
