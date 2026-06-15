# PROGRESS — Vigil MVP overnight build

> Read this first on every wake-up, alongside `NIGHT_GOAL.md`. This is the durable
> source of truth across wake-ups and crashes. Tick items, append journal entries.

Branch: `feature/vigil-mvp` · Started: 2026-06-14 (overnight)

## Backlog status

- [x] 1. Schema + domain models (migrations, models, enums, factories) — 8 tests green
- [x] 2. Project module (arcus CRUD + tests) — 12 tests green
- [ ] 3. Monitor module (arcus CRUD + tests)
- [ ] 4. Check engine (probes, SsrfGuard, state machine, RunCheckJob, dispatch cmd)
- [ ] 5. Incident module
- [ ] 6. Notification module (fallback chain + dedup + quiet hours)
- [ ] 7. Rollups + retention + scheduler wiring
- [ ] 8. Status pages + heartbeat ingress + tls-allowed ask endpoint
- [ ] 9. Self-monitoring + dead-man heartbeat
- [ ] (stretch) app.vigil monitor list + detail

## Journal (newest first)

- 2026-06-14 — Item 2 DONE. Project module full arcus slice (Controller/Service/Repository/
  DTOs/Requests/Resource) + 5 REST routes (auth-guarded) + 12 feature tests. Backend owns
  slug with collision retry. destroy returns the deleted resource (granular update). Full
  suite 92 green. Next: item 3 (Monitor module CRUD).
- 2026-06-14 — Item 1 DONE. 13 migrations, 6 enums, 11 models, 8 factories for the full
  Vigil schema. Schema smoke test (8 cases) + full suite (80) green on sqlite. Next: item 2
  (Project module CRUD).
- 2026-06-14 — Charter + branch set up. Verified bootstrap: Laravel 13.2, PHP ^8.3,
  Pest 4, existing Auth module. Loop armed.

## Decisions taken autonomously

- **Partitioning is split across two migrations.** `check_results` base table is portable
  (plain `id` PK, `monitor_id` indexed, NO FK); a separate migration
  (`..._partition_check_results_table`) applies composite PK `(id, checked_at)` +
  monthly `RANGE` partitions **only when `DB::getDriverName() === 'mysql'`**. Reason: the
  test suite runs on SQLite `:memory:` (per phpunit.xml) and would choke on MySQL
  partition DDL. Tests cover the portable shape; production gets partitions.
- **Enum case naming = UPPER** (e.g. `MonitorType::HTTP`) to match existing
  `StatusEnum`/`UserStatus`, even though Boost's generic guideline suggests TitleCase —
  "follow existing conventions" wins.
- **Result enum named `CheckOutcome`** (not `CheckResultEnum`) to avoid confusion with the
  `CheckResult` model.
- Added `leased_until` to `monitors` to support the dispatch-lease design (PLAN §5).

## BLOCKED / needs your review
(none yet)
