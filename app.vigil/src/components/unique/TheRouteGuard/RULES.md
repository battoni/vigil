---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — TheRouteGuard

> For AI agents. Last updated: 2026-05-29.

## Purpose

Component-based navigation guard wrapping the app's `<RouterView>`. Owns all route-level concerns for authenticated views: session restore on hard navigation, request cancellation on route leave, and three validation cases (unauthenticated, public route, staff-only route).

## Architecture

- **Hard navigation** (`onBeforeMount` → `onHardNavigation`): fired on first load/refresh. Checks existing auth state; if not authenticated, calls `GetMeService` to restore session from cookie. After session resolves (success or failure), runs `routeMiddleware()` and sets `canRender = true` only if the route is allowed.
- **Soft navigation** (`onBeforeRouteLeave` → `onRouteLeave` → `onSoftNavigation`): fired on in-app navigation. Cancels all in-flight HTTP requests first, then runs `routeMiddleware()` and calls `next(boolean)`.
- **`canRender` ref gates the slot**: children do NOT mount until session is verified. This prevents private views from firing their own `onMounted` API calls before auth is known.

## Intentional Decisions

- **`canRender` stays `false` on blocked routes**: if `routeMiddleware()` returns `false`, the slot never renders, avoiding a flash of private content before the redirect completes.
- **`.catch(() => {})` on `GetMeService`**: session fetch errors (network failure, 401) are intentionally swallowed here. `onSessionResolved()` fires via `.finally()`, and `unauthenticatedRouteCase()` handles the redirect to login. Do not add toast notification here.
- **`onSessionResolved` name**: called regardless of whether session fetch succeeded or failed — it means "we now know the session state, proceed with routing." It does NOT mean authentication was successful.
- **`onRouteLeave` wrapper**: satisfies the app.vigil convention that lifecycle hooks must be one-liners calling named functions.
- **`cancelAllRequests()` before routing**: prevents browser's ~6-connection-per-domain limit from being exhausted by stale requests from the previous page.

## Validation Cases (`useValidationCases`)

- `unauthenticatedRouteCase()` — not authenticated + private route → push to `/entrar` (no toast)
- `publicRouteCase()` — authenticated + public route → push to `{ name: 'home' }` (hard: silent; soft: toast)
- `staffRouteCase()` — non-staff + `meta.requiresStaff` route → push to `{ name: 'home' }` (hard: silent; soft: toast)

## Route Meta Flags

- `meta.isPublic = true` — login/auth routes. Read by `publicRouteCase` and `unauthenticatedRouteCase`.
- `meta.requiresStaff = true` — admin-only routes. Read by `staffRouteCase`. Checked against `useUserStore().hasEditAuth` (i.e. `role_slug === ROLES.SUPERADMIN`).

## Wiring

`TheRouteGuard` wraps `<RouterView>` in `App.vue`:

```vue
<TheRouteGuard>
  <RouterView />
</TheRouteGuard>
```

Auth routes must carry `meta: { isPublic: true }`. Do NOT use the old `meta: { public: true }` key — `TheRouteGuard` checks `isPublic` specifically.

## Do Not

- Do not add a `catch` with a toast to the `GetMeService` chain — the redirect to login IS the error handling.
- Do not remove `canRender` gating — doing so causes children to mount before auth is known.
- Do not move session restore back to `router.beforeEach` — this component deliberately replaces that pattern.
- Do not add more than three validation cases without updating both `useValidationCases.ts` and this RULES.md.
