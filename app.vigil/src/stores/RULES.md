---
version: 1.2.0
origin: vigil
based-on: 1.1.0
---

# RULES — src/stores/

> For AI agents. Last updated: 2026-05-22.

## Purpose

Global Pinia stores for app-wide shared state that must persist across components and is not owned by a single module.

## Global Store vs. Module Store — Decision Guide

**Put it here (`src/stores/`)** when:

- State is consumed by 2+ unrelated modules (e.g. UI state needed by layout AND feature views)
- State has no clear module owner

**Put it in `src/modules/ModuleName/store.ts`** when:

- State is specific to one module's domain (e.g. `useUserStore` lives in `User/store.ts`)
- The store is the primary source of truth for that module's entities

## Current Stores

| File          | Store ID | Purpose                                                    |
| ------------- | -------- | ---------------------------------------------------------- |
| `ui.store.ts` | `'ui'`   | App-wide UI state: sidenav open/close, viewport dimensions |

## Conventions

- File name: `camelCase.store.ts`
- Default export with `defineStore`
- Store ID matches the file name (e.g. `'ui'` for `ui.store.ts`)
- Imported via the `@Stores` alias (default import: `import useUiStore from '@Stores'`)
- Use options API style (`state`, `actions`, `getters`) for consistency with existing stores

## `ui.store.ts` specifics

- **`useWindowSize()` is called at module level** (outside `defineStore`): this is intentional — `@vueuse/core`'s `useWindowSize` must be called once and shared, not per-store-instance.
- **`appHeight` / `appWidth` getters**: use `() => height.value` (not `(state) => ...`) because they read from the module-level refs.

## Do Not

- **Do not store domain entities** (users, roles, etc.) in global stores — those belong in module stores.
- **Do not call `useWindowSize()` inside `defineStore`** — it must be at module level (see above).
- **Do not create a global store for state used by only one module** — put it in the module's `store.ts`.
