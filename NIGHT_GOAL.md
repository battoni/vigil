# NIGHT_GOAL — Vigil MVP autonomous build charter

> This is the goal prompt for the overnight self-paced loop. On every wake-up:
> **(1) read this file, (2) read `PROGRESS.md`, (3) run `git log --oneline -15`,
> (4) continue from the first unchecked backlog item.** Then make ONE increment,
> verify it green, commit it, update `PROGRESS.md`, and re-schedule the next wake-up.

## Objective

Implement the **Vigil MVP (v0)** described in `PLAN.md` §14 ("it pages me when a
site dies"), building on the existing **api.vigil** Laravel 13 bootstrap (arcus DDD
conventions, Pest 4). Frontend (app.vigil) is a stretch goal only after the backend
MVP is fully green.

Ground truth (verified): Laravel 13.2, PHP `^8.3`, Pest 4, existing `Auth` module at
`api.vigil/app/Modules/Auth` as the reference for layering and naming.

## Working agreement (non-negotiable)

- **Branch:** all work on `feature/vigil-mvp`. Never push. Local commits only.
- **One increment per wake-up.** Keep each turn bounded so a crash loses at most one
  in-flight increment. An increment = a coherent, testable slice (code + Pest tests).
- **Green before commit.** Run the relevant Pest tests (and Pint) for the slice; only
  commit when they pass. Commit message in imperative mood per shared-conventions, end
  with the Co-Authored-By trailer.
- **Conventions are law.** Follow `.claude/rules/arcus-api-architecture.md` exactly:
  Route → Request → Controller → DTO → Service → Repository → ApiResponse + Resource.
  Semantic repo method names (`findDueMonitors`, `recordCheckResult`, …). Mutating
  endpoints return the affected resource.
- **Don't block on me — I'm asleep.** If a real decision is needed, pick the most
  reasonable default, **document it** in `PROGRESS.md` under "Decisions taken
  autonomously", and keep moving. Only hard-stop an item if it's impossible without me
  (e.g. a secret/credential); log it under "BLOCKED" and move to the next item.
- **Honor PLAN.md's hard rules:** `check_results` has NO db-level FK (partitioned);
  SsrfGuard pins resolved IP + re-checks every redirect hop; quiet hours defer (never
  drop) and critical bypasses; Evolution-health alert routes around WhatsApp; on-demand
  TLS gated by an `ask` endpoint.

## Backlog (work top-down; keep the authoritative checklist in PROGRESS.md)

1. **Schema + domain models** — migrations, Models, Enums for: `projects`, `monitors`
   (incl. `heartbeat_token`, `last_ping_at`, state columns), `check_results`
   (RANGE-partition-ready, no FK), `monitor_uptime_hourly`, `monitor_uptime_daily`,
   `incidents`, `notification_channels`, `channel_monitor`, `notification_logs`,
   `maintenance_windows`, `status_pages`, `monitor_status_page`. Factories for each.
2. **Project module** — full arcus CRUD + routes + Pest feature tests.
3. **Monitor module** — full arcus CRUD (type-driven `config`, interval min 60s
   validation, thresholds) + routes + tests.
4. **Check engine** — `ProbeInterface`, `ProbeResult` VO, `SsrfGuard` (pin + redirect
   re-check, with unit tests for blocked ranges), `HttpProbe`/`TcpProbe`/`SslProbe`,
   `ProbeFactory`, `StateMachineService`, `RunCheckJob`, `DispatchDueChecksCommand`
   (lease, don't blind-bump), `SweepHeartbeatsCommand` + tests.
5. **Incident module** — `IncidentService` open/resolve, repository, resource, tests.
6. **Notification module** — `NotifierInterface`, Slack/Email/WhatsApp channels,
   `AlertDispatchService` fallback chain (WhatsApp→Slack→Email; Email = Resend→SMTP),
   dedup via `notification_logs`, quiet-hours defer + critical bypass, tests (fake HTTP).
7. **Rollups + retention** — `rollup:hourly`, `rollup:daily`, monthly partition
   pre-create command; schedule wiring in `routes/console.php`; tests.
8. **Status pages + heartbeat ingress** — StatusPage admin CRUD, `PublicStatusController`,
   `UptimeQueryService` (reads rollups only), `/api/internal/tls-allowed` ask endpoint,
   `/api/heartbeats/{token}` push endpoint (token-guarded, rate-limited), tests.
9. **Self-monitoring + scheduler** — `checks:dispatch` every minute, external dead-man
   heartbeat hook documented; Evolution-health internal monitor seed.

Stretch (only if 1–9 are green): app.vigil monitor list + detail per celer conventions.

## Per-iteration procedure (follow exactly each wake-up)

1. `cd api.vigil`; read PROGRESS.md; `git log --oneline -15`.
2. Pick the first unchecked sub-item. Implement it (code + tests).
3. Run its tests: `php artisan test --filter=<Relevant>` (or `./vendor/bin/pest`).
   Run `./vendor/bin/pint <touched paths>` if available.
4. If green: `git add -A && git commit`. If red: fix; if stuck after a real effort,
   log the blocker in PROGRESS.md and move to the next independent item.
5. Update PROGRESS.md: tick the item, append a one-line journal entry with the commit
   hash, note any autonomous decision/blocker.
6. **Re-schedule the next wake-up (ScheduleWakeup) passing this same charter pointer.**

## Stop condition (end the loop — omit ScheduleWakeup)

Stop only when **either**: all of backlog 1–9 are complete and green, **or** every
remaining item is hard-BLOCKED on me. In that case write a final "MORNING REPORT"
section at the top of PROGRESS.md (what shipped, what's blocked, what to review) and
do not reschedule. Otherwise, always reschedule.

## Resilience (API failures on the model side)

The recovery mechanism is the scheduled wake-up itself: every turn ends with exactly
one `ScheduleWakeup`. If a turn dies mid-flight from an API/tool error, the wake-up
scheduled by the *previous* turn re-fires this charter and I resume from the last
commit. Durable state lives in `PROGRESS.md` + git — never only in conversation memory.
