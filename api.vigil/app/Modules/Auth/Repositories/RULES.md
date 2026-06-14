---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---
# RULES — Auth Repositories

> For AI agents. Layer conventions beyond `arcus-api-architecture`.

## Role

The ONLY layer that touches Eloquent. All queries live here. Methods use **semantic,
entity-prefixed names** (`findAllUsers`, `findUserById`, `createUser`, `updateUser`,
`syncRolePermissions`) — never generic `find`/`create`.

## Intentional Decisions

- **Always re-hydrate the relation after a write**: `return $user->load('role');` after
  create/update. The Resource downstream expects the relation loaded — returning an
  unhydrated model would drop `role`/`permissions` from the response. Same for
  `RoleRepository` (`->load('permissions')`).
- **Eager-load on reads**: `with('role')` on `findAllUsers`/`findUserById`,
  `with('permissions')` on roles — prevents N+1 and feeds the Resource's `whenLoaded`.
- **Partial update via `array_filter(..., fn ($v) => $v !== null)`**: `updateUser` strips
  null DTO fields so a PATCH only updates provided keys. Note this means a field can never
  be *cleared* to null through update — intentional for these entities.
- **Backend owns the slug**: `createRole` derives `slug` with `Str::slug($dto->name)`. Slugs
  are never accepted from the client.
- **`syncRolePermissions` is delete-then-recreate**: clears existing `role->permissions()`
  then `createMany` — permissions are name-string rows, not a pivot sync.
- Find methods return `?Model`; the service/controller maps null to 404.

## Do Not

- Do not return a model from a write without `->load(...)` its relations.
- Do not accept or trust a client-supplied slug.
- Do not put business rules here — only data access.
