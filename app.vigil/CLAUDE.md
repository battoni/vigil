# app.vigil – Project Context for AI Agents

## What this is

app.vigil is a Vue 3 bootstrap app — the source template for new projects. It lives inside the `vigil` workspace.

## Tech Stack

- **Vue 3.5** — always `<script setup lang="ts">`
- **TypeScript**
- **PrimeVue** — component library (auto-imported)
- **Tailwind CSS** — utility-first styling
- **Pinia** — state management
- **Vue Router** — routing
- **Vue I18n** — internationalisation
- **Yup** — form validation via PrimeVue Forms (`@primevue/forms`)

## Conventions

Rules live in `.cursor/rules/` (symlinked from `../cortex/vue-rules/`):

- `01-vue-checklist.mdc` — mandatory checklist (always applied)
- `02-vue-imports.mdc` — import ordering
- `03-vue-script.mdc` — script structure, reactivity, patterns
- `04-vue-template.mdc` — template formatting, attribute order
- `05-module-conventions.mdc` — enums, constants, types, services

**Before editing any file**, check for a `RULES.md` in the file's folder and every parent folder up to `src/`.

## AI Prompts (in `../cortex/`)

- **`apply-vue-rules.md`** — fix convention violations in a file/folder
- **`generate-component-rules.md`** — generate a `RULES.md` for a folder

## Key Technical Facts

- **No component imports** — `unplugin-vue-components` auto-imports everything from `src/modules/*`, `src/components/*`, `src/layouts/*`, and all PrimeVue components
- **ESLint auto-fixes on save** — imports, attribute order, `import type`, `no-else-return`, empty catches (`eslint.config.ts`)
- **Module aliases**: `@Helpers`, `@Composables`, `@Interfaces`, `@UserModule`, `@AuthModule`, `@RolesModule`, etc. (see `vite.config.ts`)

## Module Structure

```
src/
  components/       ← shared components (A = atoms, M = molecules, O = organisms)
  composables/      ← shared composables
  helpers/          ← shared helpers
  layouts/          ← layout components
  modules/          ← feature modules (Auth, User, Roles, etc.)
    ModuleName/
      components/
      services/
      store/
      views/
      enums.ts
      constants.ts
      interfaces.ts
      types.ts
```
