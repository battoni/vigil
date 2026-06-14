---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — TheNavbar

> For AI agents. Last updated: 2026-05-22.

## Purpose

Orchestrator for all three navbar variants (desktop sidebar, tablet/mobile drawer, mobile bottom bar). Owns navigation state, auth logic, and permission/role filtering.

## Intentional Decisions

- **`window.resize` listener instead of `@vueuse/core`**: `syncMobileState` is registered manually in `onMounted` and removed in `onBeforeUnmount`. This is intentional — no `useWindowSize` or `useMediaQuery` dependency.
- **`items` and `settings` are defined here, not in a constants file**: nav items are reactive refs because they may need to be dynamic in future. Do not move them to a constants file.
- **Home route special-cased**: `itemsWithRoutes` uses `{ name: 'home' }` directly for the home item instead of resolving via `getI18nRouteName`. All other routes go through the helper.
- **`ROLES.SUPERADMIN` gates the Showcase route in `settings`**: the Showcase item will not appear unless `user.role_slug === ROLES.SUPERADMIN`. Do not remove this guard.
- **`mobileItems` is a fixed subset of 4 routes**: it hard-codes which route names appear in the mobile bottom bar. Add a new entry to `mobileItems` filter list when a new primary route should appear on mobile.
- **Logout navigates to `/login` (literal path), not a named route**: this is intentional to avoid i18n route resolution on logout.
- **`drawerClass` / `drawerPosition` are computed and passed to `ONavbarDrawer`**: mobile uses `position='full'` and class `'nav-drawer-mobile'`; tablet uses `position='left'` and class `'w-54'`.

## Prop & Emit Contract

No props or emits — this is a unique layout component.

## Dependencies & Context

- **`useUiStore`**: `isSidenavOpen` / `toggleSidenav` / `closeSidenav` drive drawer open state across the app.
- **`useUserStore`**: `hasPermission()` filters nav items; `user.role_slug` gates role-restricted items; `clearUser()` is called on logout.
- **`LogoutService`** from `@AuthModule`: called on logout; on failure shows an error toast.
- **`::deep(.nav-drawer-mobile)`** in this component's `<style scoped>` forces `height: 100vh` on the mobile drawer — this CSS targets `ONavbarDrawer`'s Drawer root through the scoped boundary.

## Do Not

- **Do not add navigation items directly to `ONavbarDesktop` / `ONavbarDrawer`** — all items are managed here and passed down.
- **Do not remove the `ROLES.SUPERADMIN` guard** from the Showcase settings item without a product decision.
