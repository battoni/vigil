---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — TheLayout

> For AI agents. Last updated: 2026-05-22.

## Purpose

Main authenticated application layout. Composes `TheNavbar` with a scrollable content area and an optional page header slot.

## Intentional Decisions

- **`transform-[translateZ(0)]` on `<main>`**: creates a CSS containing block for `position: fixed` descendants. This is required for `MMainDialog`'s `appendTo="self"` to scope the dialog mask inside `<main>` on desktop. Do not remove it.
- **`main-scroll` class on the inner scroll div**: `MMainDialog`'s global CSS uses `main:has(.main-dialog-root) .main-scroll { overflow: hidden }` to lock scroll when a dialog is open. The class name is part of that contract — do not rename it.
- **`useUiStore()` is called but not destructured**: this initializes the store on layout mount. The store is consumed by child components (`TheNavbar`, `ThePageHeader`). Do not remove the call.
- **`pb-[calc(5.75rem+env(safe-area-inset-bottom,0))]`** on the scroll div: bottom padding accounts for the mobile bottom navbar height (≈4.75rem) plus iOS safe area. On `md+` this resets to `pb-8`.
- **`max-h-[calc(120vh-10rem)]`** on the content wrapper: constrains the scrollable area. Intentional — adjusting this breaks the contained scroll behavior.
- **`pageHeader` slot**: renders above `<main>` with `px-8 pt-8 pb-2` padding. Use this slot with `ThePageHeader`; do not add a page header inside the default slot.

## Prop & Emit Contract

- `classes`: additional Tailwind classes applied to `<main>`. Use for per-page overrides (e.g. disabling overflow).
- `#pageHeader` slot: for `ThePageHeader`. Rendered outside the scroll container so it stays fixed at the top.
- `#default` slot: page content. Rendered inside the scrollable `main-scroll` div.

## Dependencies & Context

- **`MMainDialog`**: depends on `transform-[translateZ(0)]` on `<main>` and the `main-scroll` class. Changing either breaks dialog scoping and scroll lock.
- **`TheNavbar`**: auto-imported, always rendered. This layout is only for authenticated routes.

## Do Not

- **Do not remove `transform-[translateZ(0)]`** from `<main>` — dialog scoping breaks.
- **Do not rename `main-scroll`** — `MMainDialog` CSS targets it by class name.
- **Do not add page headers inside the default slot** — use the `#pageHeader` slot.
