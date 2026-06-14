---
version: 1.2.0
origin: vigil
based-on: 1.2.0
---
# RULES — Auth Module

> For AI agents. Captures Auth-specific decisions not covered by `arcus-api-architecture` or the laravel skill.

## Domain

Authentication, authorization, and the user/role/permission graph. Owns: `User` session
auth (Sanctum), `Role`, `RolePermission`, `UserPermission`, and the effective-permission
resolution that merges role + per-user grants.

## What belongs here

- Login / credential verification (`AuthService`)
- User CRUD (`UserController` → `UserService` → `UserRepository`)
- Role CRUD + permission syncing (`RoleController`, `RoleRepository`)
- Permission resolution (`PermissionService`, `EffectivePermissionsHelper`)

## What does NOT belong here

- The shared `User` Eloquent model lives in `app/Models/User.php` (app-wide), NOT in the
  module — only `Role`, `RolePermission`, `UserPermission` are module-local models. This is
  intentional: `User` is referenced across the whole app.
- The `ApiResponse` envelope is a shared helper (`app/Helpers/`), not module-owned.

## Permission model (non-obvious)

A user's effective permissions = **role permissions ∪ direct user permissions**, deduped.
Resolved by `User::effectivePermissions()` (see `app/Models/RULES.md`). Roles hold
permissions as rows in `role_permissions` (name strings), not a pivot to a permissions
table — there is no standalone `permissions` table. Same for `user_permissions`.

## Do Not

- Do not add a `permissions` master table or pivot — permissions are free-form name strings
  stored per-role / per-user by design.
- Do not move `User` into the module.
