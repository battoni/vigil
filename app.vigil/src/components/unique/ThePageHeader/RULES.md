---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — ThePageHeader

> For AI agents. Last updated: 2026-05-22.

## Purpose

Renders the top-of-page header row with an icon, title, and optional right-side action slot. Includes a hamburger button visible only on tablet (md) to toggle the sidebar drawer.

## Intentional Decisions

- **Hamburger is `hidden md:flex lg:hidden`**: tablet only. Mobile uses the bottom bar; desktop has the persistent sidebar. Do not change this breakpoint.
- **`title` is an i18n key**: resolved with `$t(title)` in the template. Do not pass a pre-translated string.
- **`icon` is a PrimeIcon CSS class string** (e.g. `'pi pi-home'`): rendered as an `<i>` element. Do not pass a component name or SVG.
- **Hamburger calls `uiStore.toggleSidenav()` directly**: does not emit — this component is tightly coupled to `useUiStore` for the drawer toggle.

## Prop & Emit Contract

- `icon`: PrimeIcon class string applied to an `<i>` element.
- `title`: i18n key string; resolved internally with `$t`.
- `#actions` slot: optional. Renders to the right side of the header row (e.g. CTA buttons).

## Do Not

- **Do not add a `@hamburgerClick` emit** — the store toggle is the contract; there is no need for parent awareness.
- **Do not pass a translated string** to `title` — the component resolves i18n internally.
