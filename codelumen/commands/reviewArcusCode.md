---
title: /reviewArcusCode
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/reviewArcusCode.md` — run it with `/reviewArcusCode`. **Edit the source command, not this page.**
:::

# Review Arcus Code

> This command reviews PHP files in **api.vigil/**. Run from the vigil monorepo root.

Analyze all api.vigil changes in the current branch against `main`, identify every convention violation, and fix them.

**This command runs in plan mode. Do not edit any file until the plan is approved.**

---

## Step 1 — Read the rules

Before doing anything else, read the api.vigil architecture rule file:

- `.claude/rules/arcus-api-architecture.md`

## Step 2 — Get the diff

Run `git diff main...HEAD -- api.vigil/` to identify all changed PHP files.

## Step 3 — Read changed files

For each changed PHP file, run `git diff main -- <file>` to see the exact changes.

## Step 4 — Run static analysis

Run PHPStan from the api.vigil directory:

```bash
cd api.vigil && vendor/bin/phpstan analyse --level=3 --no-progress <changed-files>
```

## Step 5 — Find violations

Check every changed file manually against the architecture rules:

- Request flow: Route → Controller → DTO → Service → Repository → ApiResponse
- FormRequest validation — never inline `$request->validate()` in controllers that have complex rules
- `snake_case` for all PHP variables, array keys, and DB field names
- No `env()` calls outside `config/` files
- Explicit return type hints on all new methods
- Method visibility order: public → protected → private
- No unused `use` imports
- Complex conditions extracted to named boolean variables
- Repository method names are semantic and entity-prefixed (e.g. `findAllRoles()`)
- Mutating endpoints return the affected resource
- **Test coverage:** a changed service, controller, or repository should have or gain a test — Pest feature test in `tests/Feature/` for endpoints, unit test in `tests/Unit/` for services/helpers with mocked repos. Flag any unit modified without corresponding coverage.

### Changelog/version gate (per-unit metadata — blocking)

For every **api.vigil unit** whose code changed on the branch — the nearest enclosing folder that holds both `RULES.md` and `CHANGELOG.md` (a module like `app/Modules/Auth/`, an architectural layer like `Services/` or `Repositories/`, a shared layer like `app/Helpers/`, or the `.config-rules` config-unit) — assert both:

1. **A new CHANGELOG entry exists.** If any non-metadata file governed by the unit changed (including tests under the mirrored `tests/` path) but the unit's `CHANGELOG.md` has no new top entry on this branch, that is a violation. Detect it: `git diff main...HEAD --name-only -- <unit>/CHANGELOG.md` is empty while the unit's code changed.
2. **Versions match.** The `version:` in the unit's `RULES.md` frontmatter must equal the top entry version in its `CHANGELOG.md`. A mismatch is a violation.

Flag each as `[A.6 — missing changelog]` or `[A.6 — version mismatch]` with the unit path and the fix. These are **blocking, not cosmetic**: the cortex propagation triage reads these versions to decide how each unit merges downstream, so a missed bump or a mismatch corrupts the triage.

## Step 6 — Present the plan

Output a structured fix plan, grouped by file. Each item must name the rule and describe the exact change:

```markdown
### api.vigil/app/Modules/Auth/Controllers/AuthController.php
- Line 24: [request-flow] DTO missing — map `$request` to `LoginDTO::from($request)` before passing to service
- Line 31: [return-type] Missing return type hint on `login()` method

### api.vigil/app/Modules/Auth/Repositories/UserRepository.php
- Line 15: [method-naming] `find()` → `findUserByUsername(string $username)`
```

If there are no violations, state **✅ No violations** and stop.

**Do not make any changes yet.** Wait for approval.

## Step 7 — Execute (after approval)

After the plan is approved, apply every fix listed. Work file by file. Re-run PHPStan to confirm clean output. Do not introduce any changes beyond what is listed in the plan.
