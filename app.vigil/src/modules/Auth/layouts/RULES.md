---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — Auth Layouts

> For AI agents. Last updated: 2026-05-22.

## Purpose

`AuthFlowLayout` is the shared card-and-centered-layout wrapper for all auth flow pages. It provides a logo, a titled card, and optional top/bottom action slots.

## Intentional Decisions

- **Logo is always visible** (`ALogo` in absolute position top-left): auth pages always show the logo regardless of slot usage.
- **`title` renders in the card header** before a `Divider`. If omitted, the header area has padding but no text — this is intentional for headless flows.
- **`#topActions` slot** renders above the card (e.g. back button). **`#bottomActions` slot** renders below the card (e.g. "need help" link). Neither slot has a fallback.
- **`TheCenteredLayout` handles vertical centering**: `AuthFlowLayout` delegates centering entirely to it; do not add centering classes here.
- **`max-w-[1380px]` on the container**: matches the app's max content width. The inner card is `max-w-[360px]` via `TheCenteredLayout`.

## Prop & Emit Contract

- `title`: plain translated string (call site resolves i18n before passing). Renders as an `<h1>`. Pass empty string or omit to suppress it.
- `#topActions`: slot for a back link or secondary navigation above the card.
- `#bottomActions`: slot for a "need help" link or secondary CTA below the card.
- `#default`: the form component (e.g. `MLoginPhone`, `MSignUpUser`).

## Do Not

- **Do not add auth-specific logic** (routing, validation) to this layout — it is a pure presentational container.
- **Do not pass a pre-resolved i18n key** — the call site is expected to pass a translated string (uses `t('...')` before passing, not `$t` inside this component).
