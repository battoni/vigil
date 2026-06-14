---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — ONavbarDrawer

> For AI agents. Last updated: 2026-05-22.

## Purpose

Slide-in drawer navigation for tablet (md) and mobile viewports. Hidden on lg+.

## Intentional Decisions

- **`lg:hidden` on the Drawer root**: this component is never visible on desktop.
- **`drawerClass` and `drawerPosition` are passed from `TheNavbar`**: mobile uses `position='full'` + class `'nav-drawer-mobile'`; tablet uses `position='left'` + class `'w-54'`. Do not hardcode positions or classes here.
- **`onCloseAction` wraps PrimeVue's `closeCallback`**: it calls `closeCallback()` first, then emits `close`. Order matters — closing the Drawer must happen before the parent reacts.
- **`onLogoutClick` calls `closeCallback` and emits `close` before `logout`**: the drawer must close before the logout redirect navigates away, otherwise the drawer remains mounted during navigation.
- **RouterLink click handlers call `onCloseAction(closeCallback)`**: the drawer closes when the user navigates to any item.
- **`update:visible` is forwarded to parent unchanged**: the parent (`TheNavbar`) controls `uiStore.isSidenavOpen` via this event.

## Prop & Emit Contract

- `drawerClass`: CSS class(es) for the Drawer root. Comes from `TheNavbar`'s computed `drawerClass`.
- `drawerPosition`: PrimeVue Drawer position. Comes from `TheNavbar`'s computed `drawerPosition`.
- `visible`: controlled externally by `uiStore.isSidenavOpen`.
- `@close`: emitted after `closeCallback` is called — parent should sync `uiStore.isSidenavOpen` if needed.
- `@update:visible`: forwarded from PrimeVue Drawer's visibility events.
- `@logout`: bubbled to `TheNavbar`.

## Do Not

- **Do not call `emit('logout')` before closing the drawer** — the drawer must be dismissed first to avoid UI glitches during route navigation.
- **Do not add permission filtering** — items arrive pre-filtered from `TheNavbar`.
