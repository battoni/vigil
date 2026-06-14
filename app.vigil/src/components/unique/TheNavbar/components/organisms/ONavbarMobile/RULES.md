---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — ONavbarMobile

> For AI agents. Last updated: 2026-05-22.

## Purpose

Fixed bottom navigation bar for mobile viewports (< md). Shows icon-only route links and a hamburger to open the drawer.

## Intentional Decisions

- **Two root elements: a backdrop div and the nav div**: the `mobile-navbar-backdrop` div sits behind the nav bar (z-40) to fill the safe area on iOS. The actual nav is z-50. Do not merge them into one.
- **Global `<style>` (not scoped)**: hides both the backdrop and the nav bar when a PrimeVue Dialog mask is open (`body:has(.p-dialog-mask)`). This CSS must be global because PrimeVue appends the dialog mask to `<body>`, outside Vue's scoped shadow. Do not add `scoped`.
- **Icon-only links on mobile**: `RouterLink` items render only the icon `<span>`, not the label. The label prop is only used by desktop/drawer variants.
- **`exact-active-class="bg-primary-100"`** on RouterLink: active state is applied directly on the link element, not via `isExactActive` slot pattern.
- **`mobileItems` is a pre-filtered subset** passed from `TheNavbar` — it only includes routes that belong in the mobile bottom bar (max 4 items).

## Prop & Emit Contract

- `mobileItems`: pre-filtered nav items with resolved routes. Each item: `{ icon, label, route }`.
- `@toggle-drawer`: emitted when the hamburger is clicked. `TheNavbar` handles opening `ONavbarDrawer`.

## Do Not

- **Do not add `scoped` to the `<style>` block** — the `body:has(.p-dialog-mask)` selector must reach the document root.
- **Do not show nav labels on mobile** — icon-only is intentional for space constraints.
- **Do not increase `mobileItems` beyond 4** without updating the flex layout and breakpoint handling.
