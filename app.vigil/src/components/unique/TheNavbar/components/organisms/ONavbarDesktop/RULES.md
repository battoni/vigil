---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — ONavbarDesktop

> For AI agents. Last updated: 2026-05-22.

## Purpose

The sticky left sidebar navigation for desktop viewports (lg+). Hidden below lg.

## Intentional Decisions

- **`hidden ... lg:block` on the outer wrapper**: this component renders nothing below `lg`. All logic below `lg` is handled by `ONavbarDrawer` and `ONavbarMobile`.
- **`lg:sticky lg:top-8`**: the sidebar sticks as the user scrolls. The `h-[calc(100dvh-4rem)]` on the Card ensures it fills the viewport.
- **Custom `RouterLink` with `v-slot`**: uses the render prop pattern to access `isExactActive` and `href` for custom link styling. This is required because PrimeVue's `Menu` component doesn't support custom active-state classes.
- **`isExactActive` not `isActive`**: parent route segments do not get highlighted — only the exact active route.
- **Logo renders `ALogo` with `variant="min"`**: only the icon variant on desktop sidebar.
- **Settings section is in the Card `#footer` slot** (sticky bottom), not inside the scrollable content area.

## Prop & Emit Contract

- `itemsWithRoutes`: primary nav items with resolved routes. Each item: `{ icon, label, route }`.
- `settingsWithRoutes`: secondary nav items (bottom section) with resolved routes. Same shape.
- `@logout`: bubbles up to `TheNavbar` which handles the actual logout logic.

## Do Not

- **Do not add permission logic here** — items are pre-filtered by `TheNavbar` before being passed as props.
- **Do not change `isExactActive` to `isActive`** — parent routes should not appear highlighted.
