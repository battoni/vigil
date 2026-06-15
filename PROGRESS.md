# PROGRESS — Vigil MVP overnight build

> Read this first on every wake-up, alongside `NIGHT_GOAL.md`. This is the durable
> source of truth across wake-ups and crashes. Tick items, append journal entries.

Branch: `feature/vigil-mvp` · Started: 2026-06-14 (overnight)

## Backlog status

- [ ] 1. Schema + domain models (migrations, models, enums, factories)
- [ ] 2. Project module (arcus CRUD + tests)
- [ ] 3. Monitor module (arcus CRUD + tests)
- [ ] 4. Check engine (probes, SsrfGuard, state machine, RunCheckJob, dispatch cmd)
- [ ] 5. Incident module
- [ ] 6. Notification module (fallback chain + dedup + quiet hours)
- [ ] 7. Rollups + retention + scheduler wiring
- [ ] 8. Status pages + heartbeat ingress + tls-allowed ask endpoint
- [ ] 9. Self-monitoring + dead-man heartbeat
- [ ] (stretch) app.vigil monitor list + detail

## Journal (newest first)

- 2026-06-14 — Charter + branch set up. Verified bootstrap: Laravel 13.2, PHP ^8.3,
  Pest 4, existing Auth module. Loop armed. Next: backlog item 1 (schema).

## Decisions taken autonomously
(none yet)

## BLOCKED / needs your review
(none yet)
