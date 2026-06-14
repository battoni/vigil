---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — Auth Login Views

> For AI agents. Last updated: 2026-05-22.

## Purpose

Thin wrapper views that compose `AuthFlowLayout` + one login form component each. There is one view per login flow variant.

## View Map

| File                            | Form Component              | Flow                   |
| ------------------------------- | --------------------------- | ---------------------- |
| `LoginPhoneFlowView`            | `MLoginPhone`               | WhatsApp phone → OTP   |
| `LoginCodeView`                 | `MLoginCode`                | OTP code entry         |
| `LoginUsernameFlowView`         | `MLoginEmail`               | Email → OTP            |
| `LoginPasswordFlowView`         | `MLoginPasswordCredentials` | Phone/email + password |
| `LoginUsernamePasswordFlowView` | `MLoginUsernamePassword`    | Username + password    |

## Intentional Decisions

- **Each view wraps one form component**: views own only the title (i18n key) and navigation links (`#topActions`/`#bottomActions` slots). No logic lives in the view.
- **"Need help" links**: most views link to `auth.support`; the password-credentials flow links to `auth.resetPassword`. Do not normalize these — they are distinct recovery paths.
- **Back button in `LoginUsernameFlowView`**: links to `/entrar` (literal path). This is intentional — email flow is a secondary path reachable from the main login.
- **`LoginCodeView` back button** links to `auth.login` (named route): this is the phone OTP flow's canonical home.

## Do Not

- **Do not add routing logic or API calls in these views** — all logic lives in the form components.
- **Do not merge views** — each represents a distinct product auth flow that may be individually enabled or disabled.
