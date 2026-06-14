---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — src/components/

> For AI agents. Last updated: 2026-05-22.

## Purpose

Shared, reusable UI components available across all modules. Organized by atomic design level.

## Folder Map

| Folder       | Prefix    | Use for                                                                                |
| ------------ | --------- | -------------------------------------------------------------------------------------- |
| `atoms/`     | `A`       | Single-responsibility primitives with no children (e.g. `AFormError`, `ALogo`)         |
| `molecules/` | `M`       | Compositions of atoms or PrimeVue components with a focused responsibility             |
| `organisms/` | `O`       | Complex compositions of molecules; may manage their own local state                    |
| `unique/`    | `The`     | Singletons rendered once per app (navbar, page header). No prefix rule — always `The*` |
| `templates/` | _(empty)_ | Page-level slot-driven skeletons with no business logic; currently unused              |

## When to add here vs. in a module

**Add to `src/components/`** when the component is used by 2+ modules OR is logically app-wide (navigation, layout chrome, shared UI primitives).

**Add to `src/modules/ModuleName/components/`** when the component is specific to one module's domain. Do not pre-emptively promote to shared.

## Naming

- `A*` — atom
- `M*` — molecule
- `O*` — organism
- `The*` — unique singleton

Each component lives in its own folder with `ComponentName/ComponentName.vue` + `index.ts` barrel.

## Do Not

- **Do not import from sub-paths** — all public components are auto-imported by `unplugin-vue-components`; explicit imports are never needed in templates.
- **Do not put business logic** (API calls, store reads, routing) in atoms or molecules — keep those in organisms, unique components, or views.
- **Do not create a shared component for a single use case** — wait until it's needed by a second consumer.
