# PROGRESS — Vigil MVP overnight build

---

# ☀️ MORNING REPORT — MVP backlog COMPLETE (items 1–9 all green)

Good morning. The full Vigil MVP backend (PLAN.md §14 "it pages me when a site dies")
is built, tested and committed on `feature/vigil-mvp`. **Nothing pushed.** **225 tests
passing, 460 assertions; Pint clean.** The loop has stopped (no further wake-ups scheduled).

## What shipped (per item, with commit hashes)

| # | Item | Commit(s) |
| --- | --- | --- |
| 1 | Schema: 13 migrations, 6 enums, 11 models, 8 factories (check_results partition split MySQL-only) | `f47cd50` |
| 2 | Project module CRUD | `db2f376` |
| 3 | Monitor module CRUD (type-driven, interval ≥60s, heartbeat tokens) | `feb4bf9` |
| 4 | Check engine: SsrfGuard + probes + state machine + RunCheckJob + dispatch/sweep + scheduler | `520b179`, `ff7b8ba`, `ec31af3` |
| 5 | Incident module + engine event listeners | `ab1319a` |
| 6 | Notification: fallback chain + dedup + quiet hours + channel CRUD/routing | `4b9e071`, `a51993a`, `b252962` |
| 7 | Uptime rollups (hourly/daily) + partition maintenance + scheduler | `594c66b` |
| 8 | Status pages (admin + public from rollups) + heartbeat ingress + TLS ask gate | `ebd6915`, `4e15d12` |
| 9 | Dead-man switch + Evolution-health seed | (this commit) |

Plus `29cc67d` (plan + charter). Run `git log --oneline feature/vigil-mvp` for the full chain.

## Decisions I made autonomously — please review

(Full rationale in "Decisions taken autonomously" below.) Highlights:
- **`check_results` has no DB foreign key** (MySQL forbids FKs on partitioned tables); integrity is in the repository. Partitioning is a separate MySQL-only migration so the SQLite test DB is unaffected.
- **Enum cases are UPPER** (matching your existing `StatusEnum`/`UserStatus`), not Boost's TitleCase suggestion.
- **Fallback chain is deliver-once** (first channel that succeeds wins), per PLAN §6.
- **Probes own their SsrfGuard call** (so each redirect hop is re-vetted) — slight signature deviation from PLAN §5, functionally identical.
- **Evolution-health monitor is a heartbeat, not an http probe** — an http probe against the internal `evolution-api` host would be (correctly) blocked by SsrfGuard; a sidecar pings the heartbeat instead. See PLAN §7.
- **Daily rollup percentiles are approximate** (checks-weighted mean of hourly p50/p95) since raw rows aren't re-read for the day.

## Nothing is BLOCKED. Deferred / out-of-MVP-scope (by design, PLAN §14 "v1/later")

- `still_down` re-notify/escalation, status-page subscriptions, multi-region probes, ping/dns probes, anomaly detection — all explicitly v1+ in the plan.
- Docker Compose / Caddyfile / Dockerfile are described in PLAN §2 but not generated (infra, not app code).

## Suggested next steps

1. **Run the suite against real MySQL 8** (the partition migration + `REORGANIZE` path only execute on MySQL and are not exercised by the SQLite test run). `DB_CONNECTION=mysql php artisan migrate:fresh && php artisan test`.
2. **Review the autonomous decisions above**, especially the no-FK-on-check_results and the heartbeat-vs-http Evolution-health choice.
3. **Frontend (app.vigil)** — the stretch goal (monitor list + detail per celer conventions) was not started; backend is ready for it.
4. Generate the Docker Compose / Caddyfile / Dockerfile from PLAN §2/§13 when ready to deploy.
5. Merge `feature/vigil-mvp` when satisfied (it's a large but cohesive branch; consider reviewing per-commit — each is one green increment).

---

> Read this first on every wake-up, alongside `NIGHT_GOAL.md`. This is the durable
> source of truth across wake-ups and crashes. Tick items, append journal entries.

Branch: `feature/vigil-mvp` · Started: 2026-06-14 (overnight) · **Status: COMPLETE**

## Backlog status

- [x] 1. Schema + domain models (migrations, models, enums, factories) — 8 tests green
- [x] 2. Project module (arcus CRUD + tests) — 12 tests green
- [x] 3. Monitor module (arcus CRUD + tests) — 13 tests green
- [x] 4. Check engine — DONE
  - [x] 4a. ProbeResult VO + SsrfGuard (IP pinning, redirect re-check) + create-time SSRF wired into Monitor — 23 tests green
  - [x] 4b. Probes (Http/Tcp/Ssl) + ProbeFactory + ProbeInterface — 15 tests green
  - [x] 4c. StateMachineService + RunCheckJob + DispatchDueChecksCommand (lease) + SweepHeartbeatsCommand + scheduler — 14 tests green
- [x] 5. Incident module + event listeners — 9 tests green
- [x] 6. Notification module — DONE
  - [x] 6a. NotifierInterface + Slack/WhatsApp/Email channels (Resend→SMTP) + AlertDispatchService (fallback chain, dedup, severity, channel exclusion) + alert listeners — 8 tests green
  - [x] 6b-i. Quiet-hours deferral (defer non-critical, bypass critical, never drop) + SendDeferredAlertJob — 4 tests green
  - [x] 6b-ii. Channel CRUD API + per-monitor routing (attach/detach) endpoints + secret masking — 10 tests green
- [x] 7. Rollups + retention + scheduler wiring — 4 tests green
- [x] 8. Status pages + heartbeat ingress + tls-allowed — DONE
  - [x] 8a. StatusPage admin CRUD + routing + UptimeQueryService (rollups only) + public status endpoint — 8 tests green
  - [x] 8b. Heartbeat ingress POST /api/heartbeats/{token} — 4 tests green
  - [x] 8c. tls-allowed ask endpoint for Caddy on-demand TLS — 3 tests green
- [x] 9. Self-monitoring + dead-man heartbeat + Evolution-health seed — 5 tests green
- [ ] (stretch) app.vigil monitor list + detail

## Frontend backlog (app.vigil) — see APP_PLAN.md (authority)

- [x] P0. Backend read endpoints for the dashboard — `GET monitors/{id}/uptime`, `/checks`,
  `/uptime-series`; UptimeQueryService moved to Monitor module; MonitorMetricsService + CheckResultResource — 5 tests green
- [ ] P1. Monitors dashboard (project switcher + Project CRUD, Monitor list, create/edit dialog, pause/delete)
- [ ] P2. MonitorDetail (charts, uptime cards, recent checks) + Incidents view + acknowledge
- [ ] P3. Channels CRUD + per-monitor routing UI
- [ ] P4. Status-page builder + public status page

Frontend conventions: celer-01..08; mirror `modules/User`; tests via celer-testing
(Vitest/VTU, Testing Library + MSW, Playwright). Charts = PrimeVue Chart. Project switcher
in TheLayout. Run `npm run lint` + `npm run test` in app.vigil.

## Journal (newest first)

- 2026-06-15 — P0 DONE (frontend phase started). Added dashboard read endpoints:
  GET monitors/{id}/uptime, /checks, /uptime-series. Moved UptimeQueryService from StatusPage to
  Monitor module (uptime is monitor-domain; PublicStatusResource import updated). Added
  MonitorMetricsService (uptime summary + recent raw checks + hourly/daily series), CheckResultResource,
  RollupRepository series reads. 5 tests; full suite 230 green. APP_PLAN.md committed (20e3a77). Next:
  P1 (Monitors dashboard in app.vigil) — first real frontend increment.
- 2026-06-14 — Item 9 DONE → ALL BACKLOG COMPLETE. PingDeadManSwitchCommand
  (monitoring:deadman-ping, scheduled everyMinute) GETs config('vigil.deadman_url'), no-ops when
  unset, swallows ping errors so the tick never breaks. config/vigil.php added. VigilSeeder seeds
  a system project + heartbeat "Evolution WhatsApp Session" monitor with exclude_channels
  ['whatsapp'] (idempotent firstOrCreate); wired into DatabaseSeeder. 5 tests. Full suite 225
  green. Loop STOPPED — MVP backlog 1-9 complete. MORNING REPORT written at top.
- 2026-06-14 — Item 8 COMPLETE. 8b: HeartbeatService + POST /api/heartbeats/{token}
  (unauthenticated, throttle:120,1) records an up CheckResult, refreshes last_ping_at, and if the
  monitor was DOWN flips it UP firing MonitorRecovered (resolves incident + recovery alert);
  paused monitors only update last_ping_at; unknown token → 404. 8c: GET /api/internal/tls-allowed
  ?domain= returns 200 only for a registered status_pages.custom_domain else 404 (Caddy on-demand
  TLS ask gate). 7 tests. Full suite 220 green. Next: item 9 (self-monitoring / dead-man heartbeat
  + Evolution-health seed) — the last backlog item.
- 2026-06-14 — Item 8a DONE. StatusPage module: admin CRUD + attach/detach monitors with
  pivot (group_name, sort_order) + 7 auth routes. UptimeQueryService reads ONLY rollups (hourly
  for 24h, daily for 7/30/90d) → uptime %. PublicStatusController GET /api/status/{slug}
  (unauthenticated, throttle:60,1, is_public only) → PublicStatusResource with monitors grouped,
  overallStatus, and per-monitor uptime. 8 tests incl. public page rendering from rollups + 404
  for private/unknown. Full suite 213 green. Next: 8b (heartbeat ingress).
- 2026-06-14 — Item 7 DONE. RollupService: hourly aggregation from raw check_results
  (checks_total/up, uptime_ratio, p50/p95/max via nearest-rank percentile in PHP — portable to
  sqlite), daily aggregation from hourly (checks-weighted mean for percentiles, documented
  approximation). RollupRepository upserts idempotently on (monitor_id,bucket_start). Commands
  rollup:hourly (hourlyAt 5), rollup:daily (dailyAt 00:30), checks:partition-maintenance (monthly,
  MySQL-only REORGANIZE pmax → next month, no-op on sqlite). Registered + scheduled (verified). 4
  tests. Full suite 205 green. Next: item 8 (status pages + heartbeat ingress + tls-allowed).
- 2026-06-14 — Item 6 COMPLETE. 6b-i: quiet-hours deferral (QuietHoursWindow parses
  monitor.config.quiet_hours incl. overnight; non-critical deferred via delayed
  SendDeferredAlertJob fired at window end, critical bypasses — never dropped). 6b-ii: Channel
  CRUD (controller/service/repository/DTOs/requests/resource) + 7 auth routes incl. per-monitor
  routing attach/detach with pivot (min_severity, notify_on_recovery); secret config keys
  (api_key, resend_api_key) masked in responses. 14 tests. Full suite 201 green. Next: item 7
  (rollups + retention + partition maintenance).
- 2026-06-14 — Item 6a DONE. Delivery core: NotifierInterface + SlackChannel (webhook),
  WhatsAppChannel (Evolution API), EmailChannel (Resend→SMTP sub-chain, overridable methods),
  NotifierFactory, ChannelRepository + NotificationLogRepository. AlertDispatchService: fixed
  WhatsApp→Slack→Email fallback (deliver-once, fall through on failure), dedup via
  notification_logs.hasDelivered, severity routing (min_severity vs event), notify_on_recovery,
  exclude_channels (Evolution-health routes around WhatsApp). Listeners SendAlertOnMonitorWentDown/
  Recovered registered AFTER incident listeners (incident exists before alert lookup). 8 tests
  incl. fallback, dedup, all-failed, email sub-chain, end-to-end via engine. Full suite 187 green.
  Next: 6b (Channel CRUD API + routing endpoints + quiet-hours deferral).
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
- **Fallback chain is deliver-once** (first successful channel wins, then stop) — per PLAN §6
  "If a channel fails, fall through". Not broadcast-to-all. notification_logs records a FAILED row
  per failed attempt and a SENT row for the delivering channel, so "which delivered" is auditable.
- **Quiet-hours deferral deferred to 6b.** 6a sends immediately. Quiet-hours config will live in
  monitor.config (e.g. {quiet_hours:{start,end,tz}}); non-critical deferred via a delayed job,
  critical bypasses. NOT YET IMPLEMENTED — flagged so the "never drop" guarantee is finished in 6b.
- **Evolution-health WhatsApp exclusion** implemented generically via monitor.config.exclude_channels
  (e.g. ['whatsapp']); the seeded Evolution-health monitor (item 9) must set this.

## BLOCKED / needs your review
(none yet)
