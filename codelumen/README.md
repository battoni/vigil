# CodeLumen

Code conventions and best practices documentation for battoni.dev projects — published as a VitePress site.

## Getting Started

```bash
npm install
npm run docs:dev
```

Other scripts:

```bash
npm run docs:build    # production build
npm run docs:preview  # preview production build locally
```

## Contents

| File | Topic |
| ---- | ----- |
| `conventions.md` | General coding conventions |
| `formatting-reference.md` | Vue/TS formatting reference with examples |
| `architecture.md` | Project architecture overview |
| `concepts.md` | Core concepts and principles |
| `implementation.md` | Implementation patterns |
| `forms.md` | Form patterns with PrimeVue Forms + Yup |
| `git-flow.md` | Git branching and commit conventions |
| `technologies.md` | Tech stack decisions |
| `day-to-day.md` | Day-to-day development workflow |

## AI Tooling

- `.cursorrules.template` — base template for generating `.cursorrules` files in new projects
- `formatting-reference.md` — also used as reference by AI agents when `ai/vue-rules/` is not present
