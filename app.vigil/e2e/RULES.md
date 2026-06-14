---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---
# RULES — app.vigil/e2e/

> For AI agents. Last updated: 2026-05-31.

## Purpose

Playwright end-to-end tests for all shipped app.vigil flows. Tests use route-interception
(`page.route`) so no real api.vigil backend is required.

## Stack

- Playwright (Chromium) — `playwright.config.ts` at app.vigil root
- Run: `cd app.vigil && npm run test:e2e` or `make e2e`
- Dev server: auto-started via `webServer` (`npx vite --mode test --port 5173`) — no SSL
- Retries: 1 locally, 2 on CI

## Directory layout

```
e2e/
  fixtures/auth.ts   ← shared mock user/role + authenticated page fixture
  auth/              ← login (all modes), sign-up, forgot-password
  users/             ← users CRUD
  roles/             ← roles & permissions
```

## Route interception pattern

```typescript
await page.route('**/auth/login**', route =>
  route.fulfill({ status: 200, contentType: 'application/json',
    body: JSON.stringify({ data: mockUser }) })
);
```

Mock auth/me in beforeEach for protected routes — TheRouteGuard calls it on hard navigation.

## Selectors

Use ID attributes set by PrimeVue props (`#loginUsername`, `#loginPassword`, `#email`)
or `getByRole`/`getByText`. Never CSS classes — they change with PrimeVue theme upgrades.

## Do Not

- Do not add `fixed sleep` — use `waitForLoadState` + `waitFor` instead.
- Do not use `.p-*` class selectors — use semantic ids/roles.
- Do not start a real backend — route-interception covers all flows.
