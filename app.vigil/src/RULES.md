---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — App Root (App.vue)

> For AI agents. Last updated: 2026-05-22.

## Purpose

Application root component. Mounts the global `Toast` provider and the `RouterView` outlet.

## Intentional Decisions

- **`Toast` is placed before `RouterView`**: PrimeVue Toast renders in a portal; placing it first ensures it is always in the DOM before any view tries to add a toast.
- **No layout here**: all layout (navbar, content wrapper) lives in `TheLayout` and `TheCenteredLayout`, used by individual views. `App.vue` is intentionally bare.

## Do Not

- **Do not add layout, auth guards, or composables** to `App.vue` — use router guards or the layout components instead.
- **Do not add a second `<Toast>`** anywhere in the component tree — one global instance is sufficient.
