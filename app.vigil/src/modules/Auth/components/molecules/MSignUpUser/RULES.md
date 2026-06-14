---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MSignUpUser

> For AI agents. Last updated: 2026-05-22.

## Purpose

Full user registration form with personal details, CPF, birth date, password, and terms acceptance.

## Intentional Decisions

- **CPF uses `InputMask` with `mask="999.999.999-99"`**: raw mask string including dots and dash. Backend receives the formatted string.
- **Birth date uses `InputMask` with `mask="99/99/9999"`**: Brazilian date format `DD/MM/YYYY`. Backend receives the formatted string.
- **`password` has `feedback: true`** (shows strength meter); `passwordConfirmation` has `feedback: false` — intentional asymmetry.
- **`passwordConfirmation` uses `.test('match', ...)` with `this.parent.password`**: Yup's `oneOf` shorthand doesn't work well when both fields update; custom test is more reliable.
- **Terms checkbox links to `/termos`** with `target="_blank"`: opens in a new tab. Do not change to an in-app route.
- **On success navigates to `home`** directly after registration.

## Prop & Emit Contract

No props or emits — self-contained registration form.

## Do Not

- **Do not strip CPF or birth date formatting** before sending to the API — backend handles formatted strings.
- **Do not add `autocomplete` to CPF or birth date fields** — browser autofill for these is not supported by the current form.
