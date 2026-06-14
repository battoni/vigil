---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---
# RULES — Database (factories, seeders, migrations)

> For AI agents. Conventions beyond `arcus-api-architecture` §3–4.

## Seeders

- **`DatabaseSeeder` orchestrates order, and order matters**: `RoleSeeder` →
  `SuperAdminSeeder` → `ManagerSeeder`. Roles must exist before users that reference them by
  slug. Preserve this order when adding seeders.
- **Domain seeders are idempotent**: use `updateOrCreate` keyed on a stable column (e.g.
  email) so re-seeding doesn't duplicate. See `SuperAdminSeeder`.
- **Secrets come from `env()` with safe defaults**: `env('SUPERADMIN_PASSWORD', 'password')`.
  Never hardcode real credentials. The `hashed` cast on `User::password` hashes on save —
  seed the plaintext, not a hash.
- Seeders look roles up by **slug** (`where('slug', 'super-admin')`), matching the
  backend-owned slug convention.

## Factories

- `UserFactory` sets `status => UserStatus::ACTIVE` and `role_id => Role::factory()` so a
  user always gets a valid role in tests. Password is `bcrypt('password')`.
- Use `fake()->unique()` for `username`/`email` to satisfy the unique constraints.

## Do Not

- Do not reorder seeders so a user seeds before its role exists.
- Do not hardcode secrets — use `env()` with a default.
- Do not pre-hash seeded passwords (the model cast does it).
