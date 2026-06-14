---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — src/composables/

> For AI agents. Last updated: 2026-05-22.

## Purpose

Shared Vue composables — functions that use Vue reactivity APIs (`ref`, `computed`, `watch`, lifecycle hooks) and are reused across multiple components or modules.

## Composable vs. Helper vs. Store — Decision Guide

| Question                                                            | Answer →                                                  |
| ------------------------------------------------------------------- | --------------------------------------------------------- |
| Does it use `ref`, `computed`, `watch`, or lifecycle hooks?         | Composable (`src/composables/`)                           |
| Is it pure logic with no Vue reactivity?                            | Helper (`src/helpers/`)                                   |
| Does it manage global shared state that persists across components? | Store (`src/stores/` or module `store.ts`)                |
| Is it only used within one module?                                  | Module composable (`src/modules/ModuleName/composables/`) |

## Conventions

- File name: `useCamelCase.ts` (always `use` prefix)
- Exported as a named function: `export function useFoo() { ... }`
- Exported from the barrel at `src/composables/index.ts`
- Imported via `@Composables` alias

## Do Not

- **Do not add pure utility functions** (no reactivity) — those belong in `src/helpers/`.
- **Do not manage global persistent state** in a composable — use a Pinia store instead.
- **Do not put module-specific composables here** — only add when used across 2+ modules.
