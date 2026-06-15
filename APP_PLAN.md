# Vigil — Frontend (app.vigil) Implementation Plan

> Companion to `PLAN.md` (backend/product). This covers the **Vue 3.5 SPA** that
> consumes the Vigil API: the authenticated **dashboard** plus the **public status
> page**. Built on the `app.vigil` bootstrap, celer conventions
> (`.claude/rules/celer-*`), PrimeVue + Tailwind design tokens.

---

## 0. Decisions / defaults (override freely)

| Decision | Default |
| --- | --- |
| Charts (latency/uptime) | ✅ **PrimeVue `Chart`** (Chart.js wrapper, already in the stack) — no new dep |
| Public status page | **Same SPA**, separate unauthenticated route group + minimal layout (not a separate build, for now) |
| List rendering | Reuse existing `OList*` organisms (`OListCardGrid` for monitors, `OListDivided`/`OListFeed` for incidents) |
| State | One Pinia store per module (`useMonitorStore`, etc.), mirroring `User/store.ts` |
| i18n | en + pt-BR from day one (localized route paths, like the User module) |
| Projects in UI | ✅ **First-class project switcher** in the layout; monitors/incidents scoped to the active project |
| Build order | ✅ **P0 backend reads → Monitors** first, then detail/incidents → channels → status/public |

---

## 1. Principles

1. **Conventions are law** — `celer-01..08`. `<script setup lang="ts">`, import order, reactivity order, function declarations, no component imports (auto-import), role color tokens, view patterns (`TheLayout` + `ThePageHeader` + `<aside>`).
2. **Granular state, no full refetch** — mutating endpoints return the affected resource; update the local ref (the API already returns the entity on create/update/delete).
3. **Read aggregates** — uptime %/charts read rollup-backed endpoints; recent raw checks only for the last-N detail view. Mirrors backend §11.
4. **Mirror the canonical module** — every CRUD module copies the shape of `modules/User` (store, services, `MAddEdit{Entity}Form`, `M{Entity}Card`, `{Entity}View`).
5. **The public page shares nothing sensitive** — its own route group, no auth store, only `GET /api/status/{slug}` data.

---

## 2. API → module map

The backend (committed on `feature/vigil-mvp`) exposes camelCase resources (`id` as string). Endpoints:

| Module | Endpoints | Dashboard surface |
| --- | --- | --- |
| **Project** | `GET/POST/PATCH/DELETE projects` | Project switcher + simple CRUD |
| **Monitor** | `GET monitors?project_id=`, `GET/POST/PATCH/DELETE monitors/{id}` | Monitor list (status grid), create/edit dialog, detail |
| **Incident** | `GET incidents?monitor_id=&open=`, `GET incidents/{id}`, `PATCH incidents/{id}/acknowledge` | Incidents view + per-monitor history |
| **Channel** | `GET/POST/PATCH/DELETE channels`, `POST/DELETE channels/{id}/monitors/{monitorId}` | Channels CRUD + per-monitor routing UI |
| **StatusPage** | `GET/POST/PATCH/DELETE status-pages`, `POST/DELETE status-pages/{id}/monitors/{monitorId}` | Status-page builder |
| **Public** | `GET status/{slug}` (unauth) | Public status page |
| Auth/User | existing | reuse as-is |

---

## 3. ⚠️ Backend additions needed (small, before/with the dashboard)

The MVP API only wired uptime/timeseries to the **public** page. The authenticated
**monitor detail** view needs three read endpoints — all thin wrappers over services
that already exist:

1. `GET monitors/{id}/uptime` → `UptimeQueryService::uptimeForMonitor` (24h/7d/30d/90d). *(service exists; just expose it auth-guarded)*
2. `GET monitors/{id}/checks?since=` → recent raw `check_results` (last-N detail; `CheckResultRepository::findRecentForMonitor` exists) for the recent-checks table + latency sparkline.
3. `GET monitors/{id}/uptime-series?range=7d&bucket=hourly|daily` → rollup buckets (`monitor_uptime_hourly/_daily`) for the latency + uptime charts. *(new repository read; reads rollups only)*

These are ~1 small backend increment (one controller method + resource each, Pest-tested) and should land **before** the Monitor detail view. Everything else maps to existing endpoints.

---

## 4. Module skeleton (every CRUD module)

Mirror `modules/User`:

```
modules/Monitor/
  index.ts                 # barrel — re-exports public API (components auto-import separately)
  interfaces.ts            # Monitor, MonitorPayload, Uptime, CheckResult, Incident shapes
  enums.ts                 # MONITOR_TYPE, MONITOR_STATUS (UPPER_SNAKE_CASE)
  constants.ts             # type options, interval presets, status→severity map
  store.ts                 # useMonitorStore (list ref, current, granular add/replace/remove)
  services/
    getMonitors.service.ts        # GET monitors?project_id=
    getMonitor.service.ts         # GET monitors/{id}
    createMonitor.service.ts      # POST monitors  (payload built by caller)
    updateMonitor.service.ts      # PATCH monitors/{id}
    deleteMonitor.service.ts      # DELETE monitors/{id}
    getMonitorUptime.service.ts   # GET monitors/{id}/uptime        (needs §3.1)
    getMonitorChecks.service.ts   # GET monitors/{id}/checks         (needs §3.2)
    getMonitorSeries.service.ts   # GET monitors/{id}/uptime-series  (needs §3.3)
    index.ts                      # barrel (PascalCase re-exports)
  components/molecules/
    MMonitorCard/                 # status badge, uptime %, last-checked, inline pause/edit/delete
    MAddEditMonitorForm/          # type-driven fields, Yup, owns submit/cancel (footerless dialog)
  views/
    Monitors/                     # MonitorsView.view.vue + routes.ts (en + pt-BR)
    MonitorDetail/                # charts, recent checks, incident history, heartbeat URL
```

Same skeleton for **Project**, **Incident**, **Channel**, **StatusPage** (only the views/forms differ).

---

## 5. Views (per celer-07 view patterns)

All authenticated views: `TheLayout` + `#pageHeader` (`ThePageHeader`) + `<aside>` (dialogs/confirms). `#actions` hidden while a dialog is open. Permissions as `canCreate/canRead/canUpdate/canDelete` computed from `userStore.hasPermission`.

### MonitorsView (list)
- `OListCardGrid` of `MMonitorCard` — status badge (`success`/`danger`/`warn`/`subtle` per `up/down/paused/pending`), uptime % (24h), last-checked relative time.
- Filters: project (via `MSearch`/select), status, type. CTA: **New monitor**.
- Create/edit: `MMainDialog` (`isFooterless`, i18n `title`, `:key="editing?.id ?? 'new'"`) wrapping `MAddEditMonitorForm`.
- Row actions: pause/resume (`PATCH status`), delete (`useConfirm` + `<ConfirmPopup>`), edit.
- Granular updates from API responses (no refetch).

### MonitorDetail
- Header: name, target, status, type; **heartbeat monitors show a copy-able ping URL** (`heartbeatUrl` from the resource).
- **Latency chart** (response time over range, from `uptime-series`/`checks`), **uptime chart** (ratio per bucket), range switch 24h/7d/30d/90d.
- **Uptime summary** cards (24h/7d/30d/90d from `getMonitorUptime`).
- **Recent checks** table (from `getMonitorChecks`) — time, result, ms, status code, error.
- **Incident history** (`GET incidents?monitor_id=`) — `OListFeed`/timeline, acknowledge action.

### IncidentsView
- Open/resolved tabs (`OTabView`), `OListDivided` rows: monitor, cause, started, duration, ack state. Acknowledge button (`PATCH .../acknowledge`).

### ChannelsView
- CRUD for WhatsApp/Slack/Email (`MAddEditChannelForm` — type-driven config fields; secrets shown masked, matching backend). Per-monitor routing managed from the monitor edit form (attach/detach channels with `min_severity`, `notify_on_recovery`).

### StatusPagesView (builder)
- CRUD; pick monitors, assign `group_name` + `sort_order` (drag to reorder), branding (logo/colors/headline), custom domain. Preview link to the public page.

### Public status page (unauthenticated route group)
- `GET status/{slug}` → grouped monitors, `overallStatus` banner, per-monitor uptime (24h/7d/30d/90d), incident timeline (later). Minimal layout (not `TheLayout`), heavily cacheable, no auth store touched. Branding from the payload.

---

## 6. Routing & layout

- **Project switcher** in `TheLayout` (global, persisted in the Project store) — sets the active project; Monitors/Incidents views read it to scope their queries (`?project_id=`).
- Authenticated dashboard routes under the existing guarded router (localized en + pt-BR paths per module `routes.ts`, like User).
- Public status route group: e.g. `/status/:slug` (+ pt-BR alias), **outside** the auth guard. Custom-domain hosting is handled by Caddy → the SPA resolves the page by slug/host.
- Add nav entries to the dashboard sidebar (`TheLayout`) for Monitors, Incidents, Channels, Status Pages, gated by permission.

---

## 7. Components to build (new)

Molecules: `MMonitorCard`, `MAddEditMonitorForm`, `MAddEditProjectForm`, `MAddEditChannelForm`, `MAddEditStatusPageForm`, `MIncidentRow`, `MUptimeBar` (sparkline/percent), `MStatusBadge`. Organisms: `OMonitorChart` (latency + uptime via PrimeVue Chart), `OPublicStatusBoard` (public page grouped board). Reuse existing `OList*`, `MMainDialog`, `MConfirmPopup`, `MSearch`, `MOrderBy`.

---

## 8. Testing (celer-testing)

- **Vitest + VTU** (`mountWithPlugins`) for molecules (form validation, card states, badge mapping).
- **Testing Library** (`renderWithPlugins`) + **MSW** handlers for view/integration specs (list renders, create flow, granular update, acknowledge) — mirror `UsersView.view.integration.spec.ts`.
- **Playwright** e2e for the critical flow: create monitor → see it in grid → open detail; and the public status page renders.
- MSW handlers per service mirror the API resources (camelCase, `id` as string).

---

## 9. Phasing

- **P0 — backend read endpoints (§3)**: uptime, recent checks, uptime-series. *(do first; tiny)*
- **P1 — Monitors MVP**: Project CRUD (minimal) + Monitor list + create/edit + pause/delete + status grid. The "see my monitors and manage them" core.
- **P2 — Detail & incidents**: MonitorDetail (charts, recent checks, uptime cards) + Incidents view + acknowledge.
- **P3 — Channels & routing**: Channel CRUD + per-monitor channel routing UI.
- **P4 — Status pages**: builder + **public status page**.
- **Later**: drag-reorder, branding polish, incident timeline on public page, subscriptions, dark-mode QA pass.

---

## 10. Decisions confirmed (2026-06-15)

1. ✅ **Charts** — PrimeVue `Chart` (Chart.js), no new dependency.
2. ✅ **Project model in UI** — first-class **project switcher** in the layout; monitors/incidents scoped to the active project.
3. ✅ **Build order** — **P0 backend read endpoints first**, then Monitors (P1).

### Still open (decide later, doesn't block P0/P1)

- **Public page** — same SPA route group (default) vs a separate lightweight static/SSR build for cacheability/isolation. Revisit at P4.
