# app.vigil

A Vue 3 bootstrap application — the starting point for new projects.

## Tech Stack

| Layer | Technology |
| ----- | ---------- |
| Framework | Vue 3.5 + TypeScript (`<script setup>`) |
| UI | PrimeVue 4 + PrimeIcons + Tailwind CSS 4 |
| Icons | Font Awesome 7 |
| State | Pinia |
| Routing | Vue Router |
| i18n | Vue I18n |
| Forms | PrimeVue Forms + Yup |
| HTTP | Axios |
| Error tracking | Sentry |
| Build | Vite 7 |

## Getting Started

```bash
npm install
npm run dev
```

Other scripts:

```bash
npm run build       # type-check + production build
npm run lint        # ESLint --fix (also runs on save in editor)
npm run format      # Prettier
npm run type-check  # vue-tsc
```

## Project Structure

```text
src/
  components/       # Shared components  (A = atom, M = molecule, O = organism)
  composables/      # Shared composables
  helpers/          # Shared helpers
  layouts/          # Layout components
  modules/          # Feature modules
    Auth/
    Home/
    User/
  router/
  stores/
  i18n/
```

Each module follows this internal structure:

```text
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

## Key Conventions

- Components are **auto-imported** via `unplugin-vue-components` — never import `.vue` files manually
- PrimeVue components are also auto-imported via `PrimeVueResolver`
- Module aliases: `@Helpers`, `@Composables`, `@Interfaces`, `@UserModule`, `@AuthModule`, etc. (see `vite.config.ts`)
- ESLint **auto-fixes on save** — imports, attribute order, `import type`, no-else-return, empty catches

## AI Agents

This project is set up for AI-assisted development.

- **Cursor** auto-loads conventions from `.cursor/rules/` (symlinked from `../ai/vue-rules/`)
- **Claude Code** reads `CLAUDE.md` at startup
- Use `../ai/apply-vue-rules.md` to review and fix any file against conventions
- Use `../ai/generate-component-rules.md` to generate a `RULES.md` for any folder
- Component-specific rules live in `RULES.md` files placed inside the relevant folder
