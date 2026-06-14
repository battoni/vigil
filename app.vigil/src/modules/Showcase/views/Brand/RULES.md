---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — BrandView

> For AI agents.

## Purpose

Living reference for the battoni-dev brand identity: the theme's atmosphere, the monogram
and signature atoms (`ABrandMark`, `ABrandSignature`, `ABrandPattern`), the empty-state
treatment (`AEmptyState`), and the showcase chrome (corner brackets, glow, monospaced voice)
that the active theme layers onto app.vigil.

## Intentional Decisions

- **Only visible to `ROLES.SUPERADMIN`**: gated by `role_slug` in the route `beforeEnter` guard.
- **No API calls**: all data is static. This is a visual test bed, not a feature view.
- **`title` passed as a raw string** to `ThePageHeader`: internal tooling, not user-facing copy.
- **Each section is a `<section>` with a heading**: one section per brand facet. Add a section
  when a new brand atom or theme effect is introduced.

## Do Not

- **Do not remove this view** — it is the reference for the brand layer.
- **Do not add business logic** — purely a visual test bed.
- **Do not import the brand atoms** — they are auto-imported.
