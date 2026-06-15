# PROGRESS — Vigil MVP overnight build

> Read this first on every wake-up, alongside `NIGHT_GOAL.md`. This is the durable
> source of truth across wake-ups and crashes. Tick items, append journal entries.

Branch: `feature/vigil-mvp` · Started: 2026-06-14 (overnight)

## Backlog status

- [x] 1. Schema + domain models (migrations, models, enums, factories) — 8 tests green
- [x] 2. Project module (arcus CRUD + tests) — 12 tests green
- [x] 3. Monitor module (arcus CRUD + tests) — 13 tests green
- [ ] 4. Check engine (probes, SsrfGuard, state machine, RunCheckJob, dispatch cmd)
- [ ] 5. Incident module
- [ ] 6. Notification module (fallback chain + dedup + quiet hours)
- [ ] 7. Rollups + retention + scheduler wiring
- [ ] 8. Status pages + heartbeat ingress + tls-allowed ask endpoint
- [ ] 9. Self-monitoring + dead-man heartbeat
- [ ] (stretch) app.vigil monitor list + detail

## Journal (newest first)

- 2026-06-14 — Item 3 DONE. Monitor module full arcus slice + 5 auth-guarded routes + 13
  feature tests. Type-driven config validation, interval min 60s, thresholds. Heartbeat
  monitors get a generated unique token + heartbeatUrl and null next_check_at; pause clears
  / resume re-seeds next_check_at. UpdateDTO uses Spatie Optional for partial updates. Full
  suite 105 green. Next: item 4 (Check engine — biggest item).
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
- **Create-time SSRF validation deferred to item 4.** Monitor create currently validates
  shape only; `SsrfGuard.assertSafe(target)` will be wired into StoreMonitorRequest (or the
  service) when SsrfGuard is built in item 4, so http/tcp/etc. targets are rejected at
  create-time too — not just at run-time. Tracking so it isn't forgotten.
- Monitor `index` accepts `?project_id=` to scope the list (frontend monitor list per project).

## BLOCKED / needs your review
(none yet)
