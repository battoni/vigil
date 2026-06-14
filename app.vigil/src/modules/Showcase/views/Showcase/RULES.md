---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — ShowcaseView

> For AI agents. Last updated: 2026-05-26.
> This file is updated incrementally during the build session — each section added to the view gets a matching rule here.

## Purpose

Living reference view for page-level implementation patterns. Every structural decision made here is intentional and becomes the convention for all future views in this project.

---

## Page structure

```
TheLayout
  #pageHeader → ThePageHeader
    #actions   → primary CTA buttons (hidden while dialog is open)
  default slot → page content
```

### Rules

- **Always use `TheLayout`** as the root — it provides the navbar, max-width container, and scroll context.
- **Always use `ThePageHeader`** inside `#pageHeader` — never put a page title in the default slot.
- **`#actions` slot is the only place for page-level CTAs** — do not put primary action buttons anywhere else in the layout.
- **Hide actions while a dialog is open**: wrap `#actions` content in `<template v-if="!dialogVisible" #actions>`. This prevents double-interaction while the dialog mask is active.
- **Action button order** (right-to-left visual weight): secondary action first, primary CTA last.
- **Button classes for header actions**: use `celer-button-soft-primary shadow-none` for the secondary action and `celer-button-primary shadow-none` for the primary CTA. Never use PrimeVue `severity` props or `variant="outlined"` for header action buttons — they produce a muted/disabled appearance that lacks contrast against the white header.

### Example

```vue
<TheLayout>
  <template #pageHeader>
    <ThePageHeader icon="pi pi-star" title="Page Title">
      <template v-if="!dialogVisible" #actions>
        <div class="flex items-center gap-3">
          <Button class="celer-button-soft-primary shadow-none" icon="pi pi-download" label="Export" />
          <Button class="celer-button-primary shadow-none" icon="pi pi-plus" label="Add Item" />
        </div>
      </template>
    </ThePageHeader>
  </template>

  <!-- page content here -->
</TheLayout>
```

---

## Filter bar

Use `<TheFilters>` as the **first child of the default slot**, before any content. Put search inputs, sort controls, and any secondary filter controls directly inside it.

```vue
<TheFilters>
  <InputGroup class="sm:w-fit">
    <InputGroupAddon class="border-none shadow">
      <i class="pi pi-search" />
    </InputGroupAddon>
    <InputText v-model="search" class="border-none shadow" placeholder="Search…" />
  </InputGroup>

  <MOrderBy v-model:orderBy="orderBy" :options="orderByOptions" />
</TheFilters>
```

- **Search**: `InputGroup` + `InputGroupAddon` (search icon) + `InputText` — classes `border-none shadow` on both addon and input. The `InputGroup` **must** have `class="sm:w-fit"` so it does not expand to full width and wrap onto its own row.
- **Sort**: `MOrderBy` with `v-model:orderBy` and `:options`. Its internal `InputGroup` already has `w-fit` — no extra class needed on the `<MOrderBy>` itself.
- **All filter controls render inline** — `TheFilters` is a `flex-wrap` row; any full-width child breaks the layout.
- Views with no filters omit `<TheFilters>` entirely — the component is not mandatory.

---

## Dialogs

Use `<MMainDialog isFooterless>` for all create/edit dialogs. The form molecule owns its own sticky footer (Cancel + Submit); `isFooterless` disables the built-in dialog footer.

Place all `MMainDialog` instances inside `<aside>` at the end of the template — never inline.

When the view has multiple dialogs, replace the single `dialogVisible` ref with one ref per dialog, then derive a single computed flag for the `#actions` guard:

```typescript
const shortDialogVisible = ref(false);
const longDialogVisible = ref(false);

const isAnyDialogOpen = computed(() => longDialogVisible.value || shortDialogVisible.value);
```

Gate header actions with the combined flag:

```vue
<template v-if="!isAnyDialogOpen" #actions></template>
```

Use `v-if` on the form molecule inside the dialog to reset state on each open:

```vue
<MMainDialog v-model:visible="shortDialogVisible" isFooterless title="Dialog Title">
  <MFormMolecule
    v-if="shortDialogVisible"
    @onClose="shortDialogVisible = false"
    @onSuccess="onFormSuccess"
  />
</MMainDialog>
```

The `title` prop is passed as a plain string here because Vue I18n echoes unknown keys as-is. In real views, pass an i18n key instead.

### Form molecule contract

- **Location**: `src/modules/{Module}/components/molecules/MAddEdit{Entity}Form/`
- **PrimeVue Forms + Yup**: `<Form id="..." :initialValues :resolver @submit="onSubmit">` with `yupResolver(schema)`.
- **Field pattern**: `<FormField name="...">` → default slot `{ error: fieldError }` → wrapper `div.flex.flex-col.gap-2` → `<label>` + input + `<small v-if="fieldError">`.
- **Form centering**: `class="mx-auto flex w-full max-w-[500px] flex-col gap-4"` on `<Form>`.
- **Long forms** (expect scroll): add `pb-[calc(5rem+env(safe-area-inset-bottom,0px))]` to the `<Form>` class so the last field is not hidden behind the sticky footer.
- **Sticky footer**: `<div class="sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 bg-surface-0 py-4">` outside `<Form>` in the same template. `bg-surface-0` is **mandatory** — it masks scrolling content so form fields don't bleed through behind the buttons.
- **Gap filler**: immediately after the sticky footer div, add `<div class="absolute bottom-0 z-10 h-5 w-full bg-surface-0" />`. The dialog panel (`.p-dialog`) is the positioning context; this element pins to its physical bottom and covers the native padding gap regardless of scroll position. Without it, form content bleeds into the dialog's bottom padding strip when scrolled to the bottom.
- **Submit button**: `form="{form-id}" type="submit"` — connected to `<Form>` by ID, lives outside it.
- **Emits**: `onClose` (Cancel click) and `onSuccess` (after successful API response).

### Example

```vue
<!-- Parent view -->
<aside>
  <MMainDialog v-model:visible="shortDialogVisible" isFooterless title="Export">
    <MShowcaseShortForm
      v-if="shortDialogVisible"
      @onClose="shortDialogVisible = false"
      @onSuccess="onShortFormSuccess"
    />
  </MMainDialog>
</aside>

<!-- Molecule template -->
<Form
  id="showcase-short-form"
  class="mx-auto flex w-full max-w-[500px] flex-col gap-4"
  :initialValues
  :resolver
  @submit="onSubmit"
>
  <!-- fields -->
</Form>

<div class="bg-surface-0 sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
  <Button severity="secondary" label="Cancel" @click="emit('onClose')" />
  <Button form="showcase-short-form" label="Save" type="submit" :loading />
</div>

<div class="bg-surface-0 absolute bottom-0 z-10 h-5 w-full" />
```

---

## Do Not

- **Do not put `ThePageHeader` in the default slot** — it must always be in `#pageHeader`.
- **Do not show `#actions` while any dialog is open** — always gate with `v-if="!isAnyDialogOpen"`.
- **Do not place `MMainDialog` outside `<aside>`** — all dialogs live there.
- **Do not inline a form inside `MMainDialog`** — extract to a molecule.
- **Do not add business logic** to this view — it is a visual and structural reference only.
