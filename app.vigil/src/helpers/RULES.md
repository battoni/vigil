---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — src/helpers/

> For AI agents. Last updated: 2026-05-22.

## Purpose

Shared pure utility functions with no Vue reactivity. Helpers are plain TypeScript — no `ref`, no `computed`, no lifecycle hooks.

## Helper vs. Composable vs. Store — Decision Guide

| Question                                             | Answer →                                          |
| ---------------------------------------------------- | ------------------------------------------------- |
| Pure function, no Vue reactivity, no side effects?   | Helper (`src/helpers/`)                           |
| Uses `ref`, `computed`, `watch`, or lifecycle hooks? | Composable (`src/composables/`)                   |
| Manages global shared state?                         | Store                                             |
| Used in only one module?                             | Module helper (`src/modules/ModuleName/helpers/`) |

## Current Helpers

| File                              | Purpose                                                                         |
| --------------------------------- | ------------------------------------------------------------------------------- |
| `attachAuthGuard.helper.ts`       | Registers a global router `beforeEach` guard that checks auth state             |
| `bodyScrollLock.helper.ts`        | Toggles `overflow-hidden` on `document.body`                                    |
| `i18n/getI18nRouteName.helper.ts` | Resolves a localized route name with fallback to base name                      |
| `i18n/translateError.helper.ts`   | Translates an error string via i18n; falls back to `errors.unexpected` template |

## Conventions

- File name: `camelCase.helper.ts`
- Default export for single-function files; named exports for multi-function files
- Exported from `src/helpers/index.ts` barrel
- Imported via `@Helpers` alias
- Sub-folders allowed for domain grouping (e.g. `i18n/`)

## Do Not

- **Do not use Vue reactivity** (`ref`, `computed`, etc.) in helpers — use a composable instead.
- **Do not put router or i18n setup** here — only utilities that consume them after setup.
- **Do not add module-specific helpers here** — only promote when used across 2+ modules.
