---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — TermsView

> For AI agents. Last updated: 2026-05-22.

## Purpose

Static terms and conditions page using `TheCenteredLayout` directly (not `AuthFlowLayout`). Accessible from the sign-up form's terms link.

## Intentional Decisions

- **Uses `TheCenteredLayout` directly**: terms page has a wider content area than the auth card. Do not wrap in `AuthFlowLayout`.
- **Back button is absolutely positioned** (`absolute top-4 left-4`): overlays the content without affecting the centered layout.
- **Links back to `auth.entrar`** (named route): the canonical auth entry point.
- **Content is i18n-driven**: no hardcoded legal text — all content comes from `auth.flow.terms.*` keys.
