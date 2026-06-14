---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MLoginCode

> For AI agents. Last updated: 2026-05-22.

## Purpose

OTP code entry form used after a phone/email OTP is sent. Includes a 60-second resend cooldown timer.

## Intentional Decisions

- **Resend cooldown runs as a `setInterval`**: `startResendCooldown` initializes a 60-second countdown. The interval is stored in a module-level `let` (not a ref) because it doesn't need reactivity.
- **`clearResendCooldownInterval` is idempotent**: called both in `onBeforeUnmount` and before starting a new countdown to prevent leaks on rapid resends.
- **`phone` is read from `route.query.phone`**: the previous view must pass the phone number as a query param. If missing, a toast error is shown and no resend request is made.
- **`isResendTimerVisible`** is separate from the cooldown state: the timer display only appears after the first resend attempt while cooldown is still active.
- **OTP validation**: 4 digits, integers only, exact length 4. Validated via Yup on form submit.
- **On success navigates to `auth.onboarding`**: this is the next step in the registration/login flow after code verification.

## Prop & Emit Contract

No props or emits — self-contained step in the auth flow.

## Edge Cases Handled

- **Resend during active cooldown**: shows a toast explaining the wait; does not make an API call.
- **Missing phone on resend**: shows a toast and exits early.
- **Component unmounted during countdown**: interval is cleared in `onBeforeUnmount`.

## Do Not

- **Do not add a v-model for the OTP value** — PrimeVue `InputOtp` binds via `v-bind="codeFieldProps"` from PrimeVue Forms, not a local ref.
