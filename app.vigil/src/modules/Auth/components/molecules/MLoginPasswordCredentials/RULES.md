---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MLoginPasswordCredentials

> For AI agents. Last updated: 2026-05-22.

## Purpose

Multi-mode login form supporting phone (WhatsApp) or email identifier + password. Used for the "password credentials" auth flow variant.

## Intentional Decisions

- **Identifier mode switches between phone (InputMask) and email (InputText)**: `identifierMode` ref controls which input renders. `identifierModeButtonKey` forces the SelectButton to re-render when deselecting the current mode (PrimeVue SelectButton doesn't support `allowEmpty: false` without a key reset trick).
- **Mock authentication logic** (`MOCK_*` constants, `isExistingUserMock`, `createMockSessionUser`): this is temporary scaffolding until the real `auth/login` backend endpoint is implemented. See `TODO-ID-45`. Do not remove or replace until the backend is ready.
- **`selectButtonNoValidateFormControl`**: passed as `:formControl` to prevent PrimeVue Forms from validating the mode switcher as a form field.
- **`validateOnBlur: false, validateOnValueUpdate: false`**: validation only fires on submit, not on every keystroke or blur — intentional for a better UX on the identifier field which changes its validation rule based on mode.
- **`onIdentifierModeUpdate` clears the identifier field value**: when switching modes, the previous value is invalid for the new mode, so it's reset.
- **Phone digits: 10–11 digits** (Brazilian mobile numbers).

## Edge Cases Handled

- **Mode deselect attempt**: `onIdentifierModeChange` increments `identifierModeButtonKey` to force-reset the SelectButton to the current value when the user tries to deselect (PrimeVue SelectButton would otherwise allow deselect despite `allowEmpty: false`).

## Do Not

- **Do not remove the mock logic** until `TODO-ID-45` is resolved and a real backend flow exists.
- **Do not add `validateOnBlur: true`** — the identifier validation changes based on `identifierMode`, so early validation shows misleading errors.
