---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---
# RULES — Arcus Build & Config

> For AI agents. Governs api.vigil's root config files (they live at the project root where
> Laravel/Composer expect them; this folder holds their shared contract + changelog).

## Files governed

`composer.json`, `phpunit.xml`, `boost.json`, `vite.config.js`, `package.json`,
`config/*.php` conventions, `.env.example`.

## Intentional Decisions

- **Module autoloading**: `app/Modules/{Module}/` is PSR-4 autoloaded under the `App\`
  namespace via `composer.json` (`App\\: app/`). A new module needs no composer change —
  just follow the `App\Modules\{Module}\...` namespace. Run `composer dump-autoload` only
  if autoload mapping is customized.
- **`config/` is the only place `env()` may be called** (see `arcus-api-architecture`).
  Application code reads `config(...)`, never `env(...)`. `.env.example` documents every
  key the app expects — keep it in sync when adding config.
- **`phpunit.xml`** defines the test suites (`tests/Feature`, `tests/Unit`) and the test
  env (sqlite/in-memory). Tests use Pest — see the `arcus-pest-testing` skill.
- **`boost.json`** configures Laravel Boost (AI doc/search tooling). Not application config.

## Do Not

- Do not call `env()` outside `config/`.
- Do not add a config key without documenting it in `.env.example`.
- Do not place module classes outside the `App\Modules\{Module}\` namespace.
