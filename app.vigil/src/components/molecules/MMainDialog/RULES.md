---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — MMainDialog

> For AI agents. Last updated: 2026-05-22.

## Purpose

Standard add/edit action sheet rendered as a bottom-anchored PrimeVue `Dialog`. Provides a title, Cancel/Submit footer buttons, and scoped mask inside `<main>` via `appendTo="self"`.

## Intentional Decisions

- **`appendTo="self"`**: the dialog mask is scoped to the parent `<main>` element (not `body`), so the navbar stays visible on desktop. The closest ancestor must establish a containing block — `TheLayout`'s `<main>` does this with `transform-[translateZ(0)]`. Rendering outside `TheLayout` will break mask scoping.
- **Default `modal: false`, `closeOnEscape: false`, `dismissableMask: true`**: intended for explicit add/edit flows — users commit via Cancel/Submit but tapping outside still closes. Do not flip these defaults without a product reason.
- **`position: 'bottom'` default**: the bottom-slide animation only applies when position is `bottom`. The `<style>` block's keyframes target `.p-dialog-bottom .main-dialog-root`.
- **`<style>` is global (not scoped)**: when PrimeVue teleports elements, Vue scoped styles don't match. Keep this block global. Do not add `scoped`.
- **`main-dialog-root` class is required**: the slide animation selectors rely on it. Do not remove it from `dialogClass`.
- **`main-dialog-content-wrapper` class on the content div**: `MMainDialog`'s CSS uses `main:has(.main-dialog-root) .main-scroll { overflow: hidden }` to lock scroll when open. This depends on `main-dialog-root` being present.
- **`cancelButtonProps` / `submitButtonProps` use factory defaults** (`() => ({ ... })`): prevents shared-object mutation across instances.
- **Translation happens inside the component**: `title`, `cancelButtonProps.label`, and `submitButtonProps.label` are i18n keys resolved with `$t` internally. Pass keys, not pre-translated strings.
- **Default footer Cancel + Submit buttons are sticky**: `sticky bottom-0 z-10` keeps buttons visible when content overflows. Do not remove this wrapper.

## Prop & Emit Contract

- `v-model:visible`: required. Controls dialog open/close. The component never closes itself — emits drive close.
- `title`: i18n key for the `<h2>` header. Ignored when `#header` slot is provided.
- `isHeadless`: hides the header entirely (title + close button). Use instead of conditionally providing `#header`.
- `isFooterless`: hides the footer entirely (buttons + slot). Use when no footer is needed.
- `contentClass`: applied to the content wrapper div. Defaults to `flex min-h-0 flex-col gap-4`.
- `headerClasses` / `footerClasses`: forwarded to PrimeVue via `pt:header` / `pt:footer`.
- `cancelButtonProps` / `submitButtonProps`: `{ severity, variant, label }` where `label` is an i18n key.
- `@onClose`: fired when default Cancel is clicked. Consumer sets `visible = false` and resets state.
- `@onSubmit`: fired when default Submit is clicked. Consumer validates, persists, then closes.

## Edge Cases Handled

- **`#footer` slot present**: default Cancel/Submit are not rendered — they are mutually exclusive with the `#footer` slot.
- **`isHeadless` with `closable: true`**: `isHeadless` takes precedence — the close button is not rendered.

## Do Not

- **Do not add `scoped`** to the `<style>` block — it breaks styles for teleported elements.
- **Do not remove `main-dialog-root`** from `dialogClass` — animation and scroll-lock CSS depends on it.
- **Do not pass pre-translated strings** to `title` or button `label` — the component calls `$t` internally.
- **Do not override `appendTo`** — mask scoping depends on `appendTo="self"` + `TheLayout`'s containing block.
- **Do not use for global confirms** — use `useConfirm()` + `MConfirmPopup` for destructive confirmations.

## Dependencies & Context

- **`TheLayout`**: requires `transform-[translateZ(0)]` on `<main>` for `appendTo="self"` to scope the mask correctly. See `TheLayout/RULES.md`.
- **`main-scroll` CSS contract**: `main:has(.main-dialog-root) .main-scroll { overflow: hidden }` locks scroll during dialog. Defined in this component's global `<style>`.
