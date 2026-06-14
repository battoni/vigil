---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — ALogo

> For AI agents. Last updated: 2026-05-22.

## Purpose

Renders the app logo as an `<img>` tag, switching between a full-width and a compact minimum variant.

## Intentional Decisions

- **Logo PNGs are colocated in this folder**, not in `src/assets/`. Do not move them — the component imports them by relative path.
- **`variant='full'` is the default**: explicit `withDefaults` keeps the prop optional at the call site.
- **Wrapper `div` is `flex`**: sizing is controlled by the parent (e.g. `max-w-[100px]`), not by the component itself. The `w-full` on the `<img>` fills whatever the parent constrains.

## Prop & Emit Contract

- `variant`: `'full'` renders the horizontal logo; `'min'` renders the icon-only version. No other values are accepted.

## Do Not

- **Do not add hardcoded width/height** on this component — size from the parent container.
- **Do not add dark-mode variants here** without adding the corresponding PNG assets alongside.
