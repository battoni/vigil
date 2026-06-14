---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MLoginPhone

> For AI agents. Last updated: 2026-05-22.

## Purpose

WhatsApp phone number entry form. Looks up whether the phone has an existing account and routes accordingly.

## Intentional Decisions

- **Lookup, not login**: `LookupPhoneService` only checks if the phone is registered. It does not authenticate. Navigation after lookup depends on `data.hasAccount`.
- **`hasAccount: true`** → navigates to `auth.entrarCodigo` with `phone` query param (OTP flow).
- **`hasAccount: false`** → navigates to `auth.onboarding` (sign-up flow).
- **`phoneRaw` is passed unmodified to the API and as query param**: the InputMask value includes the formatting mask characters `(99) 99999-9999`. The backend is expected to handle both raw digits and formatted values.
- **Social login buttons are non-functional stubs**: Google, Facebook, and email buttons render but have no `@click` handlers. They are UI placeholders.

## Prop & Emit Contract

No props or emits — self-contained step in the auth flow.

## Do Not

- **Do not strip digits before passing to `LookupPhoneService`** — the current implementation passes the masked string; changing this without updating the backend contract will break lookup.
