---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MLoginForm

> For AI agents. Last updated: 2026-05-22.

## Purpose

Username + password login form for the standard credential-based auth flow. Used in `LoginUsernamePasswordFlowView`.

## Intentional Decisions

- **`apiError` is set on 422 responses**: HTTP 422 surfaces a field-level message via `AFormError`; any other error shows a generic toast. This distinction is intentional.
- **`messageKey` extracted from `data.message` or first `data.errors` value**: the backend may return errors in either format; the component normalizes both.
- **Uses `IftaLabel`** (not `FloatLabel`): the "inside the top" label variant from PrimeVue for username/password fields.
- **Forgot password link is a `RouterLink to="/esqueci-minha-senha"`**: hardcoded path intentionally (not a named route).
- **On success**: sets user + permissions in store, shows welcome toast, pushes to `home`.

## Prop & Emit Contract

No props or emits — self-contained login form.

## Do Not

- **Do not add field-level inline error messages** — this form uses `AFormError` for API errors only; Yup errors are shown via `IftaLabel` validation states.
