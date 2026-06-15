# Vigil — Self-Hosted Monitoring System (Implementation Plan)

> Working name: **Vigil** (Latin "watchful"). Placeholder — rename freely.
>
> A standalone, pendulum-style product: a monitoring layer you deploy on its own
> VPS and point at any site/endpoint to get uptime checks, multi-channel alerting
> (WhatsApp + Slack + Email), 1-minute check resolution (DOWN confirmed within
> `confirmation_threshold` intervals), full forever-history, and public status pages.

---

## 0. Decisions locked in

| Decision | Choice |
| --- | --- |
| Stack | **Laravel 13 (PHP 8.3+) API + Vue 3.5 SPA** (arcus + celer conventions) — verified against the api.vigil bootstrap (Laravel 13.2, PHP `^8.3`, Pest 4) |
| WhatsApp | **Evolution API**, self-hosted (Baileys / WhatsApp multi-device) |
| Slack | Incoming webhook |
| Email | **Dedicated Resend account → SMTP fallback** (own sending subdomain, e.g. `alerts.battoni.dev`) |
| Scope | **Single-org, many projects/sites** (multi-tenant deferred) |
| Deploy | **Single VPS + Docker Compose** |
| Database | **MySQL 8** — monthly RANGE partitioning + scheduled rollup tables |
| History | **Keep forever** (no pruning; raw partitioned + aggregates for fast reads) |
| Check interval | Configurable, **minimum 60s** |
| Status pages | Public, **custom domains** (Caddy on-demand TLS) from day one |

---

## 1. Principles (non-negotiable)

1. **Runs off the monitored infra.** Its own VPS, its own DB, its own everything. A monitor that shares fate with what it watches is useless.
2. **An alert is never lost.** Delivery is itself a fallback chain: **WhatsApp → Slack → Email**. If a channel fails, fall through and log which one delivered.
3. **The watcher is watched.** One small *external* dead-man's-switch pages you if Vigil itself dies (§13).
4. **Confirm before crying wolf.** N consecutive failures before declaring DOWN — no false positives from a single blip.
5. **Read aggregates, not raw.** Status pages and long-range graphs read rollup tables; raw rows are for recent detail + forensics. This keeps queries flat as history grows forever.
6. **Pendulum-shaped.** Reusable bootstrap, arcus/celer conventions, Repository layer so the engine isn't married to MySQL.

---

## 2. Architecture — Docker Compose topology

```
                          ┌─────────────────────────────────────────────┐
   Internet ── :443 ──▶   │  caddy  (TLS, reverse proxy, on-demand TLS    │
                          │          for status-page custom domains)      │
                          └───────┬───────────────────────┬──────────────┘
                                  │ /api, dashboard, status │ /evolution (internal only)
                          ┌───────▼────────┐      ┌─────────▼──────────┐
                          │  app           │      │  evolution-api     │
                          │  FrankenPHP    │      │  (WhatsApp gateway)│
                          │  Laravel API + │      └─────────┬──────────┘
                          │  Vue build     │                │
                          └───┬───────┬────┘                │
            ┌─────────────────┘       └──────────┐          │
   ┌────────▼────────┐        ┌──────────────────▼──┐  ┌────▼──────────┐
   │  scheduler      │        │  horizon            │  │  redis        │
   │  schedule:work  │        │  queue workers      │  │  queue/cache/ │
   │  (per-minute    │ ─────▶ │  (run checks in     │  │  locks        │
   │   dispatcher)   │ queues │   parallel)         │  └───────────────┘
   └─────────────────┘        └──────────┬──────────┘
                                         │
                               ┌─────────▼──────────┐
                               │  mysql 8           │
                               │  vigil DB (app)    │
                               │  evolution DB      │
                               └────────────────────┘
```

### Services

| Service | Image / base | Role |
| --- | --- | --- |
| `caddy` | `caddy:2` | TLS termination, reverse proxy, **on-demand TLS** for status custom domains |
| `app` | FrankenPHP (`dunglas/frankenphp`) | Laravel API + serves Vue build |
| `scheduler` | same app image | `php artisan schedule:work` — the per-minute tick |
| `horizon` | same app image | `php artisan horizon` — Redis queue workers running checks |
| `mysql` | `mysql:8` | app DB (`vigil`) + Evolution DB (`evolution`) |
| `redis` | `redis:7` | queue + cache + per-monitor locks |
| `evolution-api` | `atendai/evolution-api:v2` | self-hosted WhatsApp gateway |

The **scheduler** never runs checks itself — it only enqueues. **Horizon workers**
execute them concurrently with hard timeouts. That decoupling is what makes
1-minute intervals across many monitors feasible without the tick stalling on a
slow endpoint.

### `docker-compose.yml` (skeleton)

```yaml
services:
  caddy:
    image: caddy:2
    restart: unless-stopped
    ports: ["80:80", "443:443"]
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile
      - caddy_data:/data
      - caddy_config:/config
    depends_on: [app]

  app:
    build: .                      # FrankenPHP + PHP 8.4 + Laravel
    restart: unless-stopped
    env_file: .env
    depends_on: [mysql, redis]
    # serves Octane/FrankenPHP on :8000 (internal); Caddy proxies to it

  scheduler:
    build: .
    restart: unless-stopped
    env_file: .env
    command: php artisan schedule:work
    depends_on: [app, mysql, redis]

  horizon:
    build: .
    restart: unless-stopped
    env_file: .env
    command: php artisan horizon
    depends_on: [app, mysql, redis]

  mysql:
    image: mysql:8
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: vigil
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d   # creates `evolution` DB
    command: --default-authentication-plugin=caching_sha2_password

  redis:
    image: redis:7
    restart: unless-stopped
    command: redis-server --appendonly yes
    volumes: [redis_data:/data]

  evolution-api:
    image: atendai/evolution-api:v2.1.1
    restart: unless-stopped
    env_file: ./docker/evolution/.env   # DATABASE_URL -> mysql/evolution, CACHE -> redis db 1
    depends_on: [mysql, redis]
    # NOT exposed publicly; reached only via the internal network / Caddy internal route

volumes:
  caddy_data: {}
  caddy_config: {}
  mysql_data: {}
  redis_data: {}
```

> **Caddyfile** holds the dashboard/API host and a wildcard/on-demand-TLS block for
> status pages. Evolution API stays internal (no public port); the app reaches it
> at `http://evolution-api:8080`.

---

## 3. Data model (MySQL 8)

DDD modules: **Project, Monitor, Check, Incident, Notification, StatusPage, Auth.**

### Core tables

```
projects
  id, name, slug (unique), created_at, updated_at

monitors
  id, project_id (FK), name,
  type            ENUM(http, tcp, ping, ssl, keyword, heartbeat, dns),
  target          VARCHAR   -- URL / host:port / host
  config          JSON      -- method, headers, body, expected_status, keyword,
                            --   json_path, ssl_warn_days, dns_expected, etc.
  interval_seconds        INT  DEFAULT 60,   -- min 60
  timeout_ms              INT  DEFAULT 10000,
  confirmation_threshold  TINYINT DEFAULT 2, -- failures before DOWN
  recovery_threshold      TINYINT DEFAULT 1,
  status          ENUM(up, down, pending, paused, maintenance) DEFAULT pending,
  consecutive_failures    INT DEFAULT 0,
  consecutive_successes   INT DEFAULT 0,
  last_checked_at         DATETIME NULL,
  next_check_at           DATETIME NULL,     -- INDEXED — the dispatcher's hot path
  created_at, updated_at
  INDEX (next_check_at), INDEX (project_id), INDEX (status)

check_results                       -- HIGH VOLUME, partitioned (see §11)
  id, monitor_id,                    -- NO db-level FK: MySQL forbids foreign keys on
                                     --   partitioned InnoDB tables. Integrity enforced
                                     --   at the app layer via CheckResultRepository.
  checked_at DATETIME,
  result ENUM(up, down),
  response_time_ms INT NULL,
  status_code INT NULL,
  error VARCHAR NULL,                -- timeout / dns / ssl / keyword-miss / 5xx
  region VARCHAR DEFAULT 'local',    -- future multi-region probes
  PRIMARY KEY (id, checked_at),      -- partition key (checked_at) must be in every unique key
  INDEX (monitor_id, checked_at)
  -- PARTITION BY RANGE (TO_DAYS(checked_at))  monthly partitions
  -- ⚠️ Never add ->constrained() here — see §11.

monitor_uptime_hourly               -- rollup (status pages read THIS)
  id, monitor_id, bucket_start DATETIME,
  checks_total INT, checks_up INT,
  uptime_ratio DECIMAL(5,4),
  p50_ms INT, p95_ms INT, max_ms INT,
  UNIQUE (monitor_id, bucket_start)

monitor_uptime_daily                -- same shape, daily buckets (long-range graphs)

incidents
  id, monitor_id (FK), started_at, resolved_at NULL,
  cause VARCHAR, duration_seconds INT NULL,
  acknowledged_by NULL (FK users), acknowledged_at NULL,
  created_at, updated_at
  INDEX (monitor_id, started_at)

notification_channels
  id, name, type ENUM(whatsapp, slack, email),
  config JSON,        -- recipient(s), webhook url, evolution instance, etc.
  is_active BOOL DEFAULT true,
  created_at, updated_at

channel_monitor                     -- pivot: which channels alert for a monitor
  id, monitor_id, channel_id,       -- singular-alphabetical pivot name (Laravel default)
  min_severity ENUM(...) NULL, notify_on_recovery BOOL DEFAULT true

notification_logs                   -- dedup + audit + fallback record (model: NotificationLog)
  id, incident_id (FK), channel_id (FK), event ENUM(down, still_down, recovered),
  status ENUM(sent, failed, fell_back), error VARCHAR NULL, sent_at,
  INDEX (incident_id)

maintenance_windows
  id, monitor_id NULL (null = global), starts_at, ends_at, reason,
  created_at, updated_at

status_pages
  id, name, slug (unique), custom_domain VARCHAR NULL (unique),
  branding JSON,  -- logo, colors, headline
  is_public BOOL DEFAULT true, created_at, updated_at

monitor_status_page                 -- pivot (singular-alphabetical, Laravel default)
  id, status_page_id, monitor_id, group_name VARCHAR NULL, sort_order INT

users / roles / permissions         -- reuse pendulum Auth module (admin, viewer)
```

### Heartbeat (push) monitors
`type = heartbeat` monitors have **no `next_check_at`**; instead they expose a secret
ping URL (`/api/heartbeats/{token}`). A scheduled job (`heartbeats:sweep`, every minute)
flags a heartbeat DOWN when `now() - last_ping > grace_period`. Perfect for the impressao
API's queue/cron jobs.

- **Token storage:** add `heartbeat_token` (unique, indexed, nullable) + `last_ping_at`
  to `monitors`. Token is a high-entropy random string generated on create, stored
  hashed is overkill for a push-ping secret but keep it opaque and rotatable via a
  `POST /api/monitors/{id}/rotate-heartbeat` admin action. `grace_period` lives in
  `monitors.config`.

---

## 4. Laravel API — module tree (arcus DDD)

```
app/
  Models/                      User, (shared models)
  Enums/                       MonitorTypeEnum, MonitorStatusEnum, CheckResultEnum,
                               ChannelTypeEnum, IncidentEventEnum
  Helpers/                     ApiResponse
  Modules/
    Project/
      Controllers/ ProjectController.php
      Requests/    StoreProjectRequest.php, UpdateProjectRequest.php
      DTOs/        ProjectStoreDTO.php, ProjectUpdateDTO.php
      Services/    ProjectService.php
      Repositories/ ProjectRepository.php
      Resources/   ProjectResource.php
      Models/      Project.php
    Monitor/
      Controllers/ MonitorController.php
      Requests/    StoreMonitorRequest.php, UpdateMonitorRequest.php
      DTOs/        MonitorStoreDTO.php, MonitorUpdateDTO.php
      Services/    MonitorService.php, MonitorScheduleService.php
      Repositories/ MonitorRepository.php, CheckResultRepository.php
      Resources/   MonitorResource.php, CheckResultResource.php, UptimeResource.php
      Models/      Monitor.php, CheckResult.php
      Enums/       MonitorTypeEnum.php, MonitorStatusEnum.php
    Check/                     -- the engine
      Console/Commands/  DispatchDueChecksCommand.php, SweepHeartbeatsCommand.php
      Jobs/        RunCheckJob.php   (the only queued job)
      Probes/      HttpProbe.php, TcpProbe.php, PingProbe.php, SslProbe.php,
                   KeywordProbe.php, DnsProbe.php   (implement ProbeInterface)
      Services/    CheckEngineService.php, StateMachineService.php
      Support/     ProbeResult.php (value object), SsrfGuard.php
    Incident/
      Controllers/ IncidentController.php
      Services/    IncidentService.php
      Repositories/ IncidentRepository.php
      Resources/   IncidentResource.php
      Models/      Incident.php
    Notification/
      Controllers/ ChannelController.php
      Services/    AlertDispatchService.php   -- the WhatsApp->Slack->Email chain
      Channels/    WhatsAppChannel.php, SlackChannel.php, EmailChannel.php
                   (implement NotifierInterface)
      Repositories/ ChannelRepository.php, NotificationLogRepository.php
      Models/      NotificationChannel.php, NotificationsLog.php
    StatusPage/
      Controllers/ StatusPageController.php (admin), PublicStatusController.php
      Services/    StatusPageService.php, UptimeQueryService.php
      Repositories/ StatusPageRepository.php
      Resources/   StatusPageResource.php, PublicStatusResource.php
      Models/      StatusPage.php
    Auth/                      -- reused from pendulum bootstrap
```

### Key repository methods (arcus naming)
```
MonitorRepository:        findDueMonitors(): Collection, findMonitorById(int),
                          createMonitor(array), updateMonitor(int, array),
                          updateMonitorState(int, array)
CheckResultRepository:    recordCheckResult(array), findRecentForMonitor(int, $since)
IncidentRepository:       openIncident(int $monitorId, string $cause),
                          resolveIncident(int $incidentId)
UptimeQueryService:       uptimeFor(int $monitorId, Period): UptimeResource (reads rollups)
```

---

## 5. Check engine — job flow

### Scheduling (per minute)
```
Kernel::schedule()  ->  command('checks:dispatch')->everyMinute()->withoutOverlapping()
```

`checks:dispatch` (DispatchDueChecksCommand):
```
monitors = MonitorRepository.findDueMonitors()   // where next_check_at <= now()
                                                  //   and status != paused
foreach (monitors as monitor)
    // LEASE, don't blind-bump: push next_check_at out by a short lease window
    // (e.g. min(interval, 90s)) so the same monitor isn't re-dispatched while the
    // job is in flight — but NOT a full interval. The job itself sets the real
    // next_check_at = now() + interval on completion (success OR handled failure).
    MonitorRepository.leaseForCheck(monitor.id, leaseSeconds)
    RunCheckJob::dispatch(monitor.id)->onQueue('checks')
```

> **Why a lease, not a blind bump:** if the dispatcher set `next_check_at = now() +
> interval` up front and `RunCheckJob` were then lost (worker crash, queue purge,
> exception before recording), that whole interval's check would be silently skipped —
> the worst failure mode for a monitoring tool. With a short lease + commit-on-completion,
> a lost job's monitor simply becomes due again after the lease expires and gets retried.

### Running one check (RunCheckJob, on a Horizon worker)
```
1. acquire per-monitor lock (Redis, WithoutOverlapping)         // no stacking
2. SsrfGuard.resolveAndPin(monitor.target)                      // §10 — resolve DNS,
                                                                //   reject private/meta,
                                                                //   PIN the public IP
3. probe = ProbeFactory.for(monitor.type)
4. result: ProbeResult = probe.run(monitor, pinnedIp, timeout) // connect to pinned IP;
                                                                //   re-assert SSRF on every
                                                                //   redirect hop (capped)
5. CheckResultRepository.recordCheckResult(result)
6. StateMachineService.apply(monitor, result):
     if result.up:
        consecutive_failures = 0; consecutive_successes++
        if monitor.status == down and successes >= recovery_threshold:
            -> transition UP   -> IncidentService.resolve(monitor)
                               -> AlertDispatchService.send(monitor, RECOVERED)
     else (down):
        consecutive_successes = 0; consecutive_failures++
        if monitor.status == up and failures >= confirmation_threshold:
            // optional: skip during an active maintenance_window
            -> transition DOWN -> IncidentService.open(monitor, result.cause)
                               -> AlertDispatchService.send(monitor, DOWN)
7. persist monitor state + commit real next_check_at = now() + interval_seconds
   (also on a handled probe failure — never leave it on the short lease)
```

### State machine
```
PENDING ──first result──▶ UP / DOWN
UP   ──N consecutive fails (confirmation_threshold)──▶ DOWN  (open incident, alert)
DOWN ──M consecutive oks  (recovery_threshold)──────▶ UP    (resolve incident, alert)
any  ──manual──▶ PAUSED            (no checks dispatched)
any  ──maintenance window active──▶ alerts suppressed (checks still recorded)
```

### Probe types
| Type | Validates | Notes |
| --- | --- | --- |
| `http` | status code, response time, keyword present/absent, JSON-path, custom method/headers/body, redirects, basic auth | MVP. Covers "is the API healthy" too |
| `ssl` | cert chain valid + days-to-expiry ≥ threshold | MVP — warn before expiry |
| `tcp` | port connect succeeds within timeout | MVP |
| `heartbeat` | a push ping arrived within grace period | v1 — for cron/queue jobs |
| `ping` | ICMP echo | v1 — container needs `CAP_NET_RAW` |
| `dns` | record resolves / matches expected value | v1 — you have opinions about DNS now |

---

## 6. Alerting — resilient delivery

### The fallback chain (per the "never lose an alert" principle)
```
AlertDispatchService.send(monitor, event):
    channels = channels enabled for monitor, ordered by priority
    delivered = false
    foreach channel in [WhatsApp, Slack, Email] (per policy):
        try:
            channel.notify(monitor, event)
            log(sent);  delivered = true
            if channel is terminal-for-severity: break
        catch:
            log(failed);  continue   // fall through to next channel
    if not delivered:
        log(all-failed)              // surfaced on dashboard + dead-man path
```

- **Per-monitor policy:** which channels, severity routing (e.g. WhatsApp only for critical), **quiet hours**, **re-notify every X min while still down** (`still_down` event), **recovery** notice.
- **Quiet hours never *drop* an alert (reconciles with the "never lose an alert" principle):** quiet hours only *defer* non-critical alerts — the `down` event is queued and delivered when the window ends (or rolled into the recovery summary if it self-resolved). **Critical-severity** alerts bypass quiet hours entirely. Suppression must never mean silent loss; it means delayed or downgraded delivery, logged either way.
- **Dedup:** alerts fire on incident *transitions*, never per failed check (`notifications_log` is the guard).
- **Message template:** monitor name · URL · cause (status/timeout/keyword/ssl) · down-since + duration · link to incident.

### Email channel = its own mini chain
**Resend (dedicated account, `alerts.battoni.dev`) → SMTP fallback.** Same pattern: try Resend, on failure send via SMTP, log which delivered. Kept on a separate sending identity so alert email never shares fate with monitored sites.

---

## 7. Evolution API (WhatsApp) integration

- Runs as the `evolution-api` Compose service, **internal only**. App calls `POST http://evolution-api:8080/message/sendText/{instance}` with the `apikey` header.
- **One-time setup:** create an instance, fetch QR, **scan once** with a **dedicated WhatsApp number** (⚠️ never your personal line — isolates the unofficial-API ban risk).
- **Phone keepalive:** WhatsApp multi-device does **not** need the phone online 24/7 to send, **but** logs out linked devices if the phone hasn't connected in **~14 days**. Keep a cheap dedicated phone powered + on Wi-Fi anywhere with internet.
- **Session health probe:** an internal monitor checks the Evolution instance connection state. **This monitor's channel policy must explicitly exclude WhatsApp** (Slack + Email only) — a dead WhatsApp session obviously can't alert you that WhatsApp is dead. Enforce it: `AlertDispatchService` skips the WhatsApp channel for the Evolution-health monitor so the page always routes around the broken transport.
- Config (env): `EVOLUTION_BASE_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_INSTANCE`, recipient numbers.

---

## 8. Status pages (v1)

- Per-`status_page`: current state of selected monitors (grouped), uptime % for 24h/7d/30d/90d, response-time sparkline, incident history timeline, scheduled-maintenance banner.
- **Reads exclusively from `monitor_uptime_hourly`/`_daily`** → instant over any range, no matter how big history grows.
- **Custom domains** via Caddy **on-demand TLS** (`status.<client>.com` → automatic cert). Branding: logo + colors + headline from `branding` JSON.
- Heavily cached (public, read-only). Subscriptions (email/WhatsApp notify on incident) = later.

---

## 9. Frontend (Vue 3.5 / celer conventions)

**Dashboard (authenticated):**
- Monitor list — built on your `OList*` renderers (status badge, uptime %, last-checked, inline edit/pause/delete).
- Monitor detail — latency chart + uptime chart (from rollups), recent raw checks, incident history. Heartbeat monitors show a copy-able secret ping URL.
- Create/edit monitor — `MMainDialog` + `MAddEditMonitorForm` (type-driven fields; `MToggleField` for booleans).
- Channels — CRUD for WhatsApp/Slack/Email + per-monitor routing.
- Status-page builder — pick monitors, group, branding, custom domain.
- Incidents view — open/resolved, acknowledge, durations.

**Public status page:** separate unauthenticated route set, minimal, cacheable; can be the same SPA with public routes or a small SSR/static build.

---

## 10. Security

- **SSRF guard (critical — users supply the URLs):** before every fetch, resolve the target and **refuse private/loopback/link-local ranges and cloud metadata** (`127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16`, `169.254.169.254`, `::1`, etc.). Otherwise Vigil is an internal-network scanner. Runs at monitor-create validation **and** in `RunCheckJob`.
  - **Defeat TOCTOU / DNS rebinding:** a hostname clean at create-time can re-resolve to an internal IP at run-time. `SsrfGuard.resolveAndPin()` resolves the host, validates **every** returned A/AAAA record, **pins** a chosen public IP, and the probe connects to that pinned IP (host header preserved for TLS/vhost). Never resolve once for the check and again for the connection.
  - **Re-check on redirects:** the HTTP probe follows redirects, so `3xx` is a bypass vector. Re-run the full guard on **each** redirect target, pin its IP too, and **cap redirect hops** (e.g. 5). A redirect to an internal address fails the check.
- **Status-page custom domains — on-demand TLS abuse control (critical):** Caddy on-demand TLS will mint a cert for *any* domain pointed at the box, which is a Let's Encrypt rate-limit DoS. Caddy's `on_demand_tls { ask ... }` must call an internal endpoint (e.g. `GET /api/internal/tls-allowed?domain=`) that returns 200 **only** when `domain` matches a row in `status_pages.custom_domain`. No `ask` = open cert minting.
- Dashboard behind Auth (admin/viewer). **Heartbeat push endpoints** use per-monitor secret tokens (`monitors.heartbeat_token`, opaque + rotatable; see §3). Status pages are read-only public. Rate-limit public + push endpoints. Secrets in env only. Evolution API key never exposed publicly.

---

## 11. Retention & rollups (MySQL, keep-forever)

- `check_results` is **RANGE-partitioned by month** (`PARTITION BY RANGE (TO_DAYS(checked_at))`). A monthly scheduled job pre-creates next month's partition. Old partitions stay individually small and fast; archiving = detach/export a whole partition.
  - **No foreign keys on this table.** MySQL/InnoDB forbids FK constraints on partitioned tables — so `check_results.monitor_id` has **no** `->constrained()`. Referential integrity is enforced in `CheckResultRepository` (and a periodic orphan-sweep if ever needed). The partition column `checked_at` must be part of every unique key, hence `PRIMARY KEY (id, checked_at)`.
- **Rollup jobs:**
  - `rollup:hourly` (hourly) → upsert `monitor_uptime_hourly` from the last hour of raw.
  - `rollup:daily` (daily) → upsert `monitor_uptime_daily` from hourly.
- **Status pages / graphs read rollups only.** Raw is for last-N-days detail + incident forensics.
- **Sizing:** 1 monitor @ 1/min ≈ 525k rows/yr; tens of MB/yr each, single-digit GB for hundreds of monitors over years — fine on MySQL uncompressed. If disk ever bites: `ROW_FORMAT=COMPRESSED` on sealed partitions, or export old partitions to object storage (S3/R2) as CSV/Parquet.
- Backups: nightly `mysqldump` (or Percona XtraBackup) + offsite; archive sealed monthly partitions so live DB and backups don't bloat.

---

## 12. Self-monitoring — the dead-man's switch

Vigil cannot alert about its own death. The scheduler emits a heartbeat to an
**external** free service every minute (healthchecks.io free / Better Stack
heartbeat / the existing GitHub Actions cron). If Vigil stops beating, that
external service pages you. One small external dependency, on purpose.

---

## 13. Deployment & ops

- Docker Compose, named volumes, `.env`-driven. FrankenPHP for the app (single container, HTTP/2/3). Caddy auto-TLS for the dashboard host + **on-demand TLS for status domains, gated by an `ask` endpoint** (`/api/internal/tls-allowed`, §10) so only registered custom domains get certs.
- `php artisan migrate --force` on deploy; Horizon supervised by its own container (restart: unless-stopped).
- Health: app `/up`, Compose `healthcheck`s, restart policies. Horizon dashboard for queue depth/throughput.
- Provision script + Makefile (`make deploy`, `make logs`, `make backup`).

---

## 14. Phasing

### MVP (v0) — "it pages me when a site dies"
- HTTP/HTTPS + SSL + TCP probes; configurable interval (min 60s); confirmation threshold.
- Incidents (open/resolve); 3 channels (WhatsApp/Slack/Email) with fallback chain + email Resend→SMTP sub-chain.
- Dashboard: monitor CRUD, status grid, incident list, basic uptime %.
- MySQL + monthly partitioning + hourly/daily rollups.
- Docker Compose deploy; external dead-man heartbeat.
- SSRF guard.

### v1 — "it's a product"
- Public status pages with custom domains (Caddy on-demand TLS), branding, groups.
- Heartbeat/cron probes; ICMP ping; DNS probe.
- Maintenance windows; escalation/reminders (`still_down`); quiet hours.
- Latency/uptime charts from rollups; acknowledge incidents.

### Later
- Multi-region probes (lightweight remote agents reporting back — `region` already modeled).
- Status-page subscriptions; outbound webhooks / PagerDuty / Opsgenie.
- Multi-tenant (orgs/teams, per-tenant limits, billing hooks).
- Anomaly detection on latency.

---

## 15. Validation checklist

**Engine**
- [ ] `checks:dispatch` enqueues only monitors with `next_check_at <= now()` and not paused.
- [ ] Dispatch **leases** `next_check_at` (short window), not a full interval (no double-dispatch in flight).
- [ ] A **lost `RunCheckJob` does not silently skip a cycle** — the monitor becomes due again after the lease and is retried.
- [ ] `RunCheckJob` commits the real `next_check_at = now() + interval` on completion (success **and** handled failure).
- [ ] `RunCheckJob` honors per-monitor lock (no overlapping checks for one monitor).
- [ ] Hard timeout enforced; a hanging endpoint never blocks a worker beyond `timeout_ms`.
- [ ] DOWN only after `confirmation_threshold` consecutive fails; UP after `recovery_threshold`.
- [ ] Maintenance window suppresses alerts but still records checks.

**Alerting**
- [ ] One alert per incident transition (no per-check spam) — verified via `notification_logs`.
- [ ] WhatsApp failure falls through to Slack, then Email; delivering channel logged.
- [ ] Email channel: Resend failure falls through to SMTP.
- [ ] Recovery notification fires on UP transition.
- [ ] Quiet hours **defer** (never drop) non-critical alerts; **critical bypasses** quiet hours.
- [ ] Evolution-health monitor routes around WhatsApp (Slack/Email only).
- [ ] Re-notify interval respected.

**Data / retention**
- [ ] `check_results` partitioned by month; next-month partition auto-created.
- [ ] `check_results` has **no db-level FK** on `monitor_id` (would break partitioning); integrity enforced in repository.
- [ ] Hourly + daily rollups populate correctly; status page reads rollups (confirm no raw scan on long ranges).
- [ ] Full history retained (no pruning); query speed flat over a year of data.

**Security**
- [ ] SsrfGuard blocks private/loopback/link-local/metadata targets at create-time and run-time.
- [ ] SsrfGuard **pins the resolved public IP** (no TOCTOU/DNS-rebinding gap) and **re-checks every redirect hop** (capped).
- [ ] Heartbeat push requires the secret token; status pages are read-only.
- [ ] Evolution API not reachable from the public internet.

**Self-monitoring**
- [ ] Killing the Vigil box triggers the external dead-man alert within its window.

**Status pages**
- [ ] Custom domain gets an automatic cert (Caddy on-demand TLS) **only when registered** — the `ask` endpoint rejects unknown domains.
- [ ] Uptime %, latency graph, incident timeline render from rollups.

---

## 16. Open decisions to confirm before building

1. **WhatsApp number** — buy a dedicated SIM/eSIM; keep a cheap phone powered + online (re-link every ~14 days). ✅ accepted.
2. **Alert email identity** — dedicated Resend account + sending subdomain (`alerts.battoni.dev` or `mail.vigil.battoni.dev`) + SMTP fallback. ✅ accepted.
3. **App-server perf** — FrankenPHP/Octane (recommended) vs php-fpm+nginx.
4. **Status-page subscriptions** — in v1 or later? (currently later)
5. **Final name** — Vigil placeholder.
6. **Laravel major version** — ✅ confirmed: api.vigil bootstrap ships **Laravel 13.2** (PHP `^8.3`, Pest 4). Build Vigil on the bootstrap as-is; bump in lockstep with it.
