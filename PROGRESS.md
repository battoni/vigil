# PROGRESS — Vigil MVP overnight build

> Read this first on every wake-up, alongside `NIGHT_GOAL.md`. This is the durable
> source of truth across wake-ups and crashes. Tick items, append journal entries.

Branch: `feature/vigil-mvp` · Started: 2026-06-14 (overnight)

## Backlog status

- [x] 1. Schema + domain models (migrations, models, enums, factories) — 8 tests green
- [x] 2. Project module (arcus CRUD + tests) — 12 tests green
- [x] 3. Monitor module (arcus CRUD + tests) — 13 tests green
- [x] 4. Check engine — DONE
  - [x] 4a. ProbeResult VO + SsrfGuard (IP pinning, redirect re-check) + create-time SSRF wired into Monitor — 23 tests green
  - [x] 4b. Probes (Http/Tcp/Ssl) + ProbeFactory + ProbeInterface — 15 tests green
  - [x] 4c. StateMachineService + RunCheckJob + DispatchDueChecksCommand (lease) + SweepHeartbeatsCommand + scheduler — 14 tests green
- [x] 5. Incident module + event listeners — 9 tests green
- [ ] 6. Notification module (fallback chain + dedup + quiet hours)
- [ ] 7. Rollups + retention + scheduler wiring
- [ ] 8. Status pages + heartbeat ingress + tls-allowed ask endpoint
- [ ] 9. Self-monitoring + dead-man heartbeat
- [ ] (stretch) app.vigil monitor list + detail

## Journal (newest first)

- 2026-06-14 — Item 5 DONE. Incident module: repository (open idempotent, resolve computes
  duration, acknowledge, filtered list), service, resource, controller + 3 auth routes
  (index/show/acknowledge). Listeners OpenIncidentOnMonitorWentDown / ResolveIncidentOnMonitor
  Recovered wired via Event::listen in AppServiceProvider. Integration test proves a down monitor
  opens exactly one incident (dedup) and recovery resolves it with duration. Full suite 179 green.
  Next: item 6 (Notification module — fallback chain, the other big one).
- 2026-06-14 — Item 4c DONE → item 4 COMPLETE. StateMachineService (counter/threshold
  transitions, maintenance suppression), RunCheckJob (per-monitor WithoutOverlapping lock,
  probe→record→state→single state write committing real next_check_at, fires MonitorWentDown/
  MonitorRecovered events on non-suppressed transitions), CheckResultRepository,
  MaintenanceWindowRepository, MonitorRepository engine methods (findDueMonitors, findDueHeartbeats,
  leaseForCheck, updateMonitorState), DispatchDueChecksCommand (90s lease, not blind-bump),
  SweepHeartbeatsCommand. Commands registered in bootstrap/app.php; both scheduled everyMinute
  withoutOverlapping in routes/console.php (verified via schedule:list). 14 tests. Full suite
  170 green. Next: item 5 (Incident module + listener for MonitorWentDown/Recovered).
- 2026-06-14 — Item 4b DONE. ProbeInterface + HttpProbe (manual redirect following with
  per-hop SsrfGuard re-vet + IP pinning via CURLOPT_RESOLVE, expected_status/keyword/json_path
  checks, basic auth/headers/body), TcpProbe (overridable openSocket), SslProbe (overridable
  fetchCertificate, expiry vs ssl_warn_days), ProbeFactory (container-resolved per type). 15
  probe tests via Http::fake + stubbed socket/cert. Full suite 156 green. Next: 4c (state
  machine + RunCheckJob + dispatch/sweep commands).
- 2026-06-14 — Item 4a DONE. SsrfGuard built: explicit IPv4/IPv6 private/reserved/CGNAT/
  metadata/link-local range blocking, hermetic create-time assertSafe (no DNS), authoritative
  resolveAndPin (strict — rejects if ANY resolved IP is blocked), assertIpSafe for redirect
  hops. ProbeResult VO + SsrfException + PinnedTarget. Create-time SSRF wired into MonitorService
  (network types only) → 422 on target. 11 SsrfGuard unit + create-time feature tests. Full suite
  141 green. Next: 4b (probes).
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
- **ProbeInterface signature** is `run(Monitor, int $timeoutMs)` — each probe owns its
  `SsrfGuard.resolveAndPin` (the HTTP probe must re-vet each redirect hop anyway). Slight
  deviation from PLAN §5's `probe.run(monitor, pinnedIp, timeout)` but functionally identical
  and keeps SSRF enforcement co-located with the redirect-following network code.
- **TCP/SSL probes expose protected `openSocket`/`fetchCertificate`** so tests stub the
  network without real sockets; HTTP uses the fakeable Http facade.
- **Engine decoupled via domain events.** RunCheckJob/SweepHeartbeats fire `MonitorWentDown`
  / `MonitorRecovered` instead of calling Incident/Alert services directly. Items 5 (incidents)
  and 6 (alerting) register listeners. Clean seam; tests assert with Event::fake.
- **StateMachine does not persist** — it mutates the monitor in memory and returns a
  StateTransition; RunCheckJob does a single `updateMonitorState` write (status + counters +
  last_checked_at + committed next_check_at + cleared lease). One write per check.
- **Maintenance windows suppress alerts but still flip status + record** (per PLAN §5):
  transition happens, `alertSuppressed=true`, so no event fires.
- **Dispatch lease = 90s** (const in DispatchDueChecksCommand). A lost RunCheckJob makes the
  monitor due again after the lease instead of silently skipping a cycle.

## BLOCKED / needs your review
(none yet)
