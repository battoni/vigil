---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — ComponentsView

> For AI agents. Last updated: 2026-05-26.

## Purpose

Living reference for all shared UI primitives in the design system: typography, buttons, form inputs, cards, tables, dialogs, toasts, messages, tags, and badges.

## Intentional Decisions

- **Only visible to `ROLES.SUPERADMIN`**: gated by `role_slug` in the route `beforeEnter` guard.
- **No API calls**: all data is static. Data never comes from the backend.
- **`title` passed as a raw string** to `ThePageHeader`: this is internal tooling, not user-facing, so hardcoded labels are acceptable.
- **Each section is a `<section>` with a heading**: sections mirror design system categories. Add a new section when a new shared component is added to `src/components/`.

## Do Not

- **Do not remove this view** — it is the reference for AI agents and developers to see the real rendered output of shared components.
- **Do not add business logic** — purely a visual test bed.
