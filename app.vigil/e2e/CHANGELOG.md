# Changelog — app.vigil/e2e (Playwright)

All notable changes to this unit are documented here. Versioned with SemVer.
This file is the canonical source for the unit's version (RULES.md frontmatter mirrors it).

## 1.0.0 — 2026-05-31

### Added
- Initial Playwright e2e suite with route-interception (no real backend needed).
- auth/login.spec.ts: login form renders, username mode with mocked POST, validation, /entrar alias, /login-email.
- auth/signup.spec.ts: sign-up page renders with mocked auth/me.
- auth/forgot-password.spec.ts: forgot-password + support pages render.
- users/users.spec.ts: list loads from mocked GET, add dialog opens, create/archive routes wired.
- roles/roles.spec.ts: roles load, permission groups display, save PATCH verified.
- fixtures/auth.ts: shared mockUser/mockRole fixtures and authenticated page extension.
