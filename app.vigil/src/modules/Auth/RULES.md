---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — Auth Module

> For AI agents. Last updated: 2026-05-22.

## Domain

Authentication flows, session bootstrap, roles, and permissions. This module owns everything related to who the user is and what they are allowed to do.

## What belongs here

- Login, sign-up, OTP, password reset flows
- Role CRUD and permission assignment
- Session entry point (logout, `GetMeService` is in User — see below)

## What does NOT belong here

- User entity management (CRUD, status, profile picture) → `User` module
- App-wide UI state → `src/stores/ui.store.ts`

## Public Barrel (`@AuthModule`)

Exports: components, enums (`ROLES`), interfaces (all service payloads + `PermissionGroup`, `Profile`, `Permission`), services, types, views (route configs), `AuthRoutes`.

`Permission` and `Profile` are re-exported from `views/RolesAndPermissions/interfaces.ts` via the barrel — do not import them by path.

## Enums

| Enum    | Values                                                                                    |
| ------- | ----------------------------------------------------------------------------------------- |
| `ROLES` | `SUPERADMIN = 'administrator'`, `ADMIN = 'admin'`, `SUPPORT = 'support'`, `USER = 'user'` |

## Services

| Service                           | Purpose                                 |
| --------------------------------- | --------------------------------------- |
| `LoginRequestService`             | Username + password login               |
| `LoginPasswordCredentialsService` | Phone/email + password (multi-mode)     |
| `LookupPhoneService`              | Check if phone has an account           |
| `RequestEmailOtpService`          | Send OTP to phone or email              |
| `VerifyLoginCodeService`          | Verify OTP code                         |
| `LogoutService`                   | End session                             |
| `RegisterUserService`             | Sign up a new user                      |
| `RequestPasswordResetService`     | Request reset code                      |
| `ResendPasswordResetCodeService`  | Resend reset code                       |
| `ConfirmPasswordResetService`     | Submit new password + code              |
| `GetRolesService`                 | List all roles                          |
| `GetRoleService`                  | Get a single role                       |
| `CreateRoleService`               | Create a role                           |
| `UpdateRoleService`               | Update role name/description/groups     |
| `UpdateRolePermissionsService`    | Update permission flags for a role      |
| `DeleteRoleService`               | Delete a role                           |
| `GetPermissionGroupsService`      | List all permission groups              |
| `GetSupportNamesService`          | Get support admin names for recovery UI |

## Routes

`AuthRoutes` is a `RouteRecordRaw[]` composed from: `ForgotPasswordRoutes`, `LoginRoutes`, `SignUpRoutes`, `TermsRoutes`, `RolesAndPermissionsRoutes`. Register via `AuthRoutes` in the router — do not import individual route arrays.

## Cross-Module Dependencies

- **Imports `useUserStore`** from `@UserModule` in `attachAuthGuard` and `LogoutService` to clear session state on logout.
- **Does not own the `User` entity** — session user data lives in `useUserStore`.

## Do Not

- **Do not add user management features** (CRUD, archive, status) here — those belong in `User` module.
- **Do not import `Permission` or `Profile` by sub-path** — they are re-exported from the barrel.
