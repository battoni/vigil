# AI Infrastructure Changelog

A running history of every significant decision, change, or addition to vigil's AI tooling.

---

## 2026-06-14 — Fix `--init` self-mangling + realign `based-on` drift

### What changed

Two engine bugs, surfaced while preparing the first downstream sync (vigil):

1. **The engine rewrote itself into the project's vocabulary** — on *both* transform paths.
   `init_in_place()` and the regular sync's **OVERWRITE writer** both `specialize()`d *every*
   file including `cortex/sync/*.py`, so the rewrite table's `("app.vigil", app)` entries became
   `("app.<project>", app)` and doc source-names flipped (RUNBOOK's "run from vigil"). In init
   it only bit on the *next* engine load (Python holds the pristine function in memory for the
   pass — a self-modifying-code footgun); via OVERWRITE it shipped a broken engine downstream,
   where `make sync-test` actually runs it. Fix: a new `is_engine_internal()` guard makes **both**
   paths write everything under `cortex/sync/` **verbatim**, **except `manifest.tsv`** (which
   legitimately needs the renamed `app.X/api.X` path tokens for the downstream `--audit`).
   Regression checks in `test_init.sh` and `test_engine.sh` (engine byte-identical to source;
   manifest still transformed; RUNBOOK keeps the vigil name).

2. **`based-on` drift on 13 units.** The battoni-dev theme pass bumped `version` but left
   `based-on` behind, violating the vigil invariant `based-on == version` (`SCHEMA.md`). The
   sync triage then read those units as falsely **diverged** on a fresh sync (caught by
   `make sync-test-engine`, "fresh sync: 0 diverged"). Realigned all 13. Added the invariant as
   an explicit rule in the `cortex-sync` skill (+ Cursor mirror) so a future bump can't recreate it.

### Why

The engine is the one artifact that must stay pristine across the vigil→project boundary — a
corrupted copy throws spurious merge conflicts on later syncs. And a stale `based-on` quietly
poisons triage classification, the very signal a maintainer trusts to decide how a unit merges.

### Files affected

- `cortex/sync/sync.py` — `is_engine_internal()` self-exclusion in `init_in_place()`
- `cortex/sync/test_init.sh` — engine-pristine regression checks
- 13 `app.vigil/**/RULES.md` — `based-on` realigned to `version`
- `.claude/skills/cortex-sync/SKILL.md` + `.cursor/rules/cortex-sync.mdc` — `based-on==version` rule

---

## 2026-06-14 — Sync the `celer-testing` Cursor mirror to the tier model

### What changed

The Claude `celer-testing` skill was refreshed to the three-tier model during the testing
build (VTU `mountWithPlugins` for unit, Testing Library `renderWithPlugins` + MSW for the
`.integration.spec.ts` tier, Playwright for e2e, plus the JSON-reporter gotcha), but its Cursor
mirror (`.cursor/rules/celer-testing.mdc`) had drifted to the older, shorter version. Rebuilt
the mirror body byte-identical to the canonical `SKILL.md` (Cursor frontmatter only differs).
Completes `cortex/sync/SKILL-PLAN.md` §2.

### Why

Cursor agents were being handed pre-tier-model testing guidance — the two assistants must teach
the same discipline. `cursor_mirror.py` only covers rules and commands, so skill mirrors are
synced by hand.

### Files affected

- `.cursor/rules/celer-testing.mdc` — body resynced to `.claude/skills/celer-testing/SKILL.md`
- `cortex/sync/SKILL-PLAN.md` — §2 marked done

---

## 2026-06-14 — `cortex-sync` skill: propagation discipline as an auto-loading skill

### What changed

Authored the `cortex-sync` skill (planned in `cortex/sync/SKILL-PLAN.md` §1). It encodes the
metadata + propagation rules that previously lived only as prose in `cortex/sync/*.md`: triad
discipline (source + `RULES.md` + `CHANGELOG.md`), version-on-change (CHANGELOG canonical,
frontmatter mirrors it), tests-are-part-of-the-unit, sync MODE awareness
(OVERWRITE/MERGE/LOCAL/SKIP), naming/transform protection, parallel-session staging (never
`git add -A`), and one-way vigil→projects flow. Trigger-loads (not `alwaysApply`) on
`RULES.md` / `CHANGELOG.md` / `cortex/sync/**` / `.cortex-*`. Claude + Cursor mirrors authored
by hand (the mirror script covers rules and commands, not skills) with byte-identical bodies.

### Why

Written discipline only binds when an agent is pointed at it. A skill auto-loads on the right
triggers, turning the docs into default behavior — and because it is committed markdown in the
AI (OVERWRITE) layer, it propagates to every vigil-derived project via the sync system itself.

### Files affected

- `.claude/skills/cortex-sync/SKILL.md` — canonical skill
- `.cursor/rules/cortex-sync.mdc` — Cursor mirror (identical body, trigger-load globs)
- `cortex/sync/SKILL-PLAN.md` — §1 marked built; trigger-load scope decision recorded

---

## 2026-06-04 — Codelumen becomes the internal handbook (generated from rules & commands)

### What changed

Restructured Codelumen from a celer-only conventions site into the **vigil internal handbook** covering every sub-project, the workflow, and the AI tooling. A zero-dependency generator (`codelumen/scripts/generate-rules.mjs`) renders one page per `.claude/rules/*` rule **and** per `.claude/commands/*` command, plus the sidebar data — so the handbook can never drift from the canonical sources. Added authored overview pages (monorepo map, AI tooling, per-project, access control) and retired the stale prose pages. Command pages live under the Cortex sidebar group.

### Why

The handbook had drifted from the real rules (the "Liquen leak"). Generating from `.claude/` makes the published docs exactly what the tooling enforces, with no second copy to maintain.

### Files affected

- `codelumen/scripts/generate-rules.mjs` — generator (rules + commands), escapes bare `<…>` so source placeholders don't break the Vue build
- `codelumen/rules/**`, `codelumen/commands/**` — generated, committed
- `codelumen/.vitepress/config.mts` — sidebar wiring + mermaid rendering
- `codelumen/{monorepo,ai-tooling,access-control}.md`, `codelumen/projects/*.md` — authored
- retired `formatting-reference.*`, `conventions.md`, `implementation.md`, `concepts.md`, `architecture.md`, `html-templates.md`

---

## 2026-06-04 — `.claude`/`.cursor` mirror sync gate

### What changed

`.claude` is now the **single canonical source** for every rule and command; `.cursor/rules/*.mdc` are generated mirrors with identical bodies. `cortex/sync/cursor_mirror.py` regenerates them (`make sync-mirror`) or verifies them (`make sync-mirror-check`). The check runs inside `make pre-commit` and as a CI job, so a drifted mirror can never reach `main`.

### Why

The two formats had silently diverged across nearly every rule. A canonical source plus an enforced gate keeps Claude Code and Cursor users governed by exactly the same rule.

### Files affected

- `cortex/sync/cursor_mirror.py` — new
- `Makefile` — `sync-mirror`, `sync-mirror-check`; `pre-commit` runs the check first
- `.github/workflows/ci.yml` — `mirror-sync` job
- `.cursor/rules/*` — re-synced from `.claude`

---

## 2026-06-04 — GitHub OAuth access gate for hosted apps

### What changed

Added `codelumen/middleware.ts` (Vercel Edge Middleware) gating the deployed handbook behind **GitHub OAuth** with a username allowlist (`ALLOWED_GITHUB_USERS`). The middleware runs only on Vercel, so local/static serving stays ungated. Documented on the handbook's Access Control page; the same pattern protects any app we host for a client.

### Why

Codelumen — and future client apps — must be reachable online but visible only to specific people. Vercel Hobby's built-in protections don't fit (paid, or Vercel-team-only), so access is enforced at the edge.

### Files affected

- `codelumen/middleware.ts` — new (`jose` + `@vercel/edge`)
- `codelumen/.env.example`, `codelumen/vercel.json` — env template + `/_auth` rewrite exclusion
- `codelumen/access-control.md` — docs

---

## 2026-06-04 — `make handoff` client-delivery eject

### What changed

`cortex/sync/handoff.py` (`make handoff`) produces a clean copy of the repo for client delivery: it exports the committed tree, **strips all vigil AI tooling** (`.claude`, `.cursor`, `cortex`, `codelumen`, `plans`, `CLAUDE.md`, tooling Makefile/CI targets), **keeps the component `RULES.md`**, and re-inits git so the tooling isn't recoverable from history. Supports `PROJECTS=`, `OUT=`, `VERIFY=`.

### Why

When handing an app to a client we keep the reusable framework and AI tooling as our IP. The eject enforces that split technically; the contract is the legal backstop.

### Files affected

- `cortex/sync/handoff.py` — new
- `Makefile` — `handoff` target
- `cortex/README.md` — Client handoff section

---

## 2026-05-26 — Git workflow commands (`/commit`, `/publish-pr`, `/create-pr`)

### What changed

Added two new commands and expanded a third to create a full commit-to-PR workflow:

| Command | What it does |
| --- | --- |
| `/commit` | Reads all changed files, classifies them by layer and concern, groups them into bottom-up conventional commits, presents the plan for approval, then executes sequentially |
| `/publish-pr` | Pushes the current branch, runs `/create-pr` to draft the PR body, then creates a draft GitHub PR via `gh pr create` |
| `/create-pr` | Expanded — now generates title + structured body (summary bullets + test plan checklist) from the full branch diff |

Both `/commit` and `/publish-pr` are available in Claude Code (`.claude/commands/`) and Cursor (`.cursor/rules/`).

**Supporting change:** `.gitignore` updated to exclude `plans/prs/*.md` — ephemeral plan files generated during PR drafting are not committed.

### Why

The previous workflow had `/create-pr` but no standardised way to stage/commit or to push and open the PR. Agents were inconsistent about grouping commits and writing conventional commit messages. The new commands enforce bottom-up layer ordering, one-concern-per-commit discipline, and a repeatable PR format without requiring manual steps.

### Files affected

- `.claude/commands/commit.md` — new
- `.claude/commands/publish-pr.md` — new
- `.claude/commands/create-pr.md` — expanded
- `.cursor/rules/commit.mdc` — new
- `.cursor/rules/publish-pr.mdc` — new
- `.cursor/rules/create-pr.mdc` — expanded
- `.gitignore` — exclude `plans/prs/*.md`

---

## 2026-05-26 — RULES.md auto-injection hooks (app.vigil)

### What changed

Implemented automatic injection of folder-specific `RULES.md` chains before every **Edit** or **Write** on app.vigil Vue/TS files. Both Claude Code and Cursor use the same Python script with tool-specific output formatting.

**Hook pipeline:**

| Step | Detail |
| --- | --- |
| Trigger | `Edit` or `Write` on a file under `app.vigil/src/` with extension `.vue`, `.ts`, or `.tsx` |
| Script | `cortex/hooks/inject-rules-context.py` |
| Walk | From the edited file's folder up to `app.vigil/src/`, collecting every `RULES.md` |
| Priority | Closer files override parent rules |
| Limit | Output truncated at 9,000 characters with a pointer to read full files |

**New rule files:**

| Rule | Scope | Purpose |
| --- | --- | --- |
| `celer-07-view-patterns` | `app.vigil/**/*.vue`, `app.vigil/**/*.ts` | Page layout, `MMainDialog`, form molecules, granular list updates, permissions, confirm popups. Reference: `UsersView.view.vue` |
| `celer-08-rules-auto-injection` | same | Documents hook behaviour and agent obligations |
| `celer-folder-mmaindialog` | `app.vigil/src/components/molecules/MMainDialog/**` | Summary of `MMainDialog/RULES.md` — mask scoping, i18n titles, `isFooterless` |
| `celer-folder-thelayout` | `app.vigil/src/layouts/TheLayout/**` | Summary of `TheLayout/RULES.md` — containing block, `main-scroll`, `#pageHeader` |
| `celer-folder-thepageheader` | `app.vigil/src/components/unique/ThePageHeader/**` | Summary of `ThePageHeader/RULES.md` — i18n title, hamburger breakpoints, `#actions` |
| `celer-folder-form-molecule` | `app.vigil/src/modules/**/components/molecules/MAddEdit*/**` | Form molecule contract — owns form + API, emits `@onClose` / `@onSuccess` |
| `celer-folder-users-view` | `app.vigil/src/modules/User/views/Users/**` | Summary of `UsersView/RULES.md` — reference CRUD view |
| `celer-folder-theme` | `app.vigil/src/styles/theme/**` | Summary of `theme/RULES.md` — brand hex contract (`#c0e021`, `#888b8d`, `#141413`) |

`celer-folder-*` rules exist in `.cursor/rules/` only (glob-loaded when matching files are open). Claude Code receives the same folder contracts via hook injection on Edit/Write; global rules `celer-01`–`celer-08` exist in both `.claude/rules/` and `.cursor/rules/`.

**Checklist updates:** `celer-01-vue-checklist` now includes a View Patterns section and Quick Ref rows; step 1 documents auto-injection.

### Why

Folder `RULES.md` files capture intentional decisions that global conventions cannot express. Agents were inconsistent about reading them before editing. Auto-injection on Edit/Write guarantees the full chain is in context; glob-scoped `celer-folder-*` rules provide the same summaries during read/planning sessions in Cursor.

Scoped to `app.vigil/src/` only — no cross-project paths from other repos.

### Files affected

**Cortex (shared hook logic):**
- `cortex/hooks/inject-rules-context.py`
- `cortex/hooks/pre-edit-rules-claude.sh`
- `cortex/hooks/pre-edit-rules-cursor.sh`
- `cortex/generate-component-rules.md` — documents auto-injection

**Claude Code:**
- `.claude/hooks/pre-edit-rules.sh`
- `.claude/settings.json` — PreToolUse hook on `Edit|Write`
- `.claude/rules/celer-01-vue-checklist.md`
- `.claude/rules/celer-07-view-patterns.md`
- `.claude/rules/celer-08-rules-auto-injection.md`

**Cursor:**
- `.cursor/hooks.json`
- `.cursor/hooks/pre-edit-rules.sh`
- `.cursor/rules/celer-01-vue-checklist.mdc`
- `.cursor/rules/celer-07-view-patterns.mdc`
- `.cursor/rules/celer-08-rules-auto-injection.mdc`
- `.cursor/rules/celer-folder-*.mdc` (6 files)
- `.cursor/rules/generate-component-rules.mdc`

---

## 2026-05-22 — Folder-specific `RULES.md` rollout (app.vigil)

### What changed

Added 51 `RULES.md` files across `app.vigil/src/`, giving each significant folder a machine-readable contract for AI agents. Files capture folder purpose, intentional design decisions, prop/emit contracts, edge cases, and "do not" rules — never repeating what is already in global `celer-01`–`celer-06` conventions.

Agents read the chain manually during planning; from 2026-05-26 onward the pre-edit hook injects it automatically on Edit/Write (see entry above).

### RULES.md by folder

**App root**

| Path | Covers |
| --- | --- |
| `app.vigil/src/RULES.md` | `App.vue` — global Toast, no layout here |

**Shared infrastructure**

| Path | Covers |
| --- | --- |
| `app.vigil/src/components/RULES.md` | Atomic design tiers (A/M/O), auto-import contract |
| `app.vigil/src/composables/RULES.md` | Shared composable patterns |
| `app.vigil/src/helpers/RULES.md` | Helper utilities and providers |
| `app.vigil/src/stores/RULES.md` | Pinia store conventions |
| `app.vigil/src/locales/RULES.md` | Locale file structure, key ordering, `npm run sort-locales` |
| `app.vigil/src/modules/RULES.md` | Module folder structure, barrel exports, alias usage |

**Layouts**

| Path | Covers |
| --- | --- |
| `app.vigil/src/layouts/TheLayout/RULES.md` | Authenticated shell — `transform-[translateZ(0)]`, `main-scroll`, `#pageHeader` slot |
| `app.vigil/src/layouts/TheCenteredLayout/RULES.md` | Auth/marketing centered layout |

**Theme**

| Path | Covers |
| --- | --- |
| `app.vigil/src/styles/theme/RULES.md` | Brand palettes (`primary`, `surface`, `ink`), semantic tokens, forking guide |

**Components — atoms**

| Path | Covers |
| --- | --- |
| `app.vigil/src/components/atoms/AFormError/RULES.md` | Form error display |
| `app.vigil/src/components/atoms/ALogo/RULES.md` | Logo component |

**Components — molecules**

| Path | Covers |
| --- | --- |
| `app.vigil/src/components/molecules/MConfirmPopup/RULES.md` | Destructive confirm wrapper |
| `app.vigil/src/components/molecules/MDefaultModal/RULES.md` | Legacy/default modal |
| `app.vigil/src/components/molecules/MMainDialog/RULES.md` | Bottom-sheet add/edit dialog — mask scoping, i18n, scroll lock |
| `app.vigil/src/components/molecules/MOrderBy/RULES.md` | Sort selector |
| `app.vigil/src/components/molecules/MSearch/RULES.md` | Search input |
| `app.vigil/src/components/molecules/MWalletCard/RULES.md` | Wallet summary card |

**Components — organisms**

| Path | Covers |
| --- | --- |
| `app.vigil/src/components/organisms/OWalletTransactions/RULES.md` | Transaction list |

**Components — unique / navbar**

| Path | Covers |
| --- | --- |
| `app.vigil/src/components/unique/ThePageHeader/RULES.md` | Page header row, i18n title, hamburger |
| `app.vigil/src/components/unique/TheNavbar/RULES.md` | Navbar shell |
| `app.vigil/src/components/unique/TheNavbar/components/organisms/ONavbarDesktop/RULES.md` | Desktop sidebar |
| `app.vigil/src/components/unique/TheNavbar/components/organisms/ONavbarDrawer/RULES.md` | Tablet drawer |
| `app.vigil/src/components/unique/TheNavbar/components/organisms/ONavbarMobile/RULES.md` | Mobile bottom bar |

**Modules — Auth**

| Path | Covers |
| --- | --- |
| `app.vigil/src/modules/Auth/RULES.md` | Auth domain — login flows, roles, permissions, services |
| `app.vigil/src/modules/Auth/layouts/RULES.md` | Auth-specific layouts |
| `app.vigil/src/modules/Auth/views/Login/RULES.md` | Login view |
| `app.vigil/src/modules/Auth/views/SignUp/RULES.md` | Sign-up view |
| `app.vigil/src/modules/Auth/views/ForgotPassword/RULES.md` | Password reset |
| `app.vigil/src/modules/Auth/views/Terms/RULES.md` | Terms view |
| `app.vigil/src/modules/Auth/views/RolesAndPermissions/RULES.md` | Role/permission management |
| `app.vigil/src/modules/Auth/components/molecules/MLoginForm/RULES.md` | Login form router |
| `app.vigil/src/modules/Auth/components/molecules/MLoginEmail/RULES.md` | Email login step |
| `app.vigil/src/modules/Auth/components/molecules/MLoginPhone/RULES.md` | Phone login step |
| `app.vigil/src/modules/Auth/components/molecules/MLoginCode/RULES.md` | OTP code step |
| `app.vigil/src/modules/Auth/components/molecules/MLoginUsernamePassword/RULES.md` | Username/password step |
| `app.vigil/src/modules/Auth/components/molecules/MLoginPasswordCredentials/RULES.md` | Password credentials step |
| `app.vigil/src/modules/Auth/components/molecules/MSignUpUser/RULES.md` | Sign-up form |
| `app.vigil/src/modules/Auth/components/molecules/MSupportForgotPassword/RULES.md` | Forgot-password support |
| `app.vigil/src/modules/Auth/components/molecules/MAddEditProfileForm/RULES.md` | Profile edit form molecule |
| `app.vigil/src/modules/Auth/components/molecules/MPermissionPanel/RULES.md` | Permission panel |
| `app.vigil/src/modules/Auth/components/molecules/MPermissionToolbar/RULES.md` | Permission toolbar |
| `app.vigil/src/modules/Auth/components/molecules/MRolesAndPermissionsSkeleton/RULES.md` | Loading skeleton |

**Modules — Home**

| Path | Covers |
| --- | --- |
| `app.vigil/src/modules/Home/RULES.md` | Home module domain |
| `app.vigil/src/modules/Home/views/Home/RULES.md` | Home dashboard view |
| `app.vigil/src/modules/Home/views/Finance/RULES.md` | Finance/wallet view |
| `app.vigil/src/modules/Showcase/views/Showcase/RULES.md` | UI showcase view (moved to own module 2026-05-26) |

**Modules — User**

| Path | Covers |
| --- | --- |
| `app.vigil/src/modules/User/RULES.md` | User domain — CRUD services, `useUserStore`, permissions |
| `app.vigil/src/modules/User/views/Users/RULES.md` | User list view — reference CRUD pattern, granular updates |
| `app.vigil/src/modules/User/components/molecules/MAddEditUserForm/RULES.md` | User add/edit form molecule |
| `app.vigil/src/modules/User/components/molecules/MUserCard/RULES.md` | User card in list grid |

### Why

Global Vue/TS rules (`celer-01`–`celer-06`) cannot capture per-folder intentional decisions (e.g. why `MMainDialog` uses global styles, why UsersView inlines its skeleton). `RULES.md` files close that gap without bloating global rules.

### Files affected

- 51 files under `app.vigil/src/**/RULES.md` (see table above)
- No changes to `.claude/` or `.cursor/` in these commits — distribution rules added 2026-05-26

---

## 2026-05-05 — Design system rules and review command (app.vigil)

### What changed

- Added `celer-06-design-system` — typography, spacing, radius, button severity, dialog usage
- Expanded color token rules in `celer-01-vue-checklist` (brand palettes, semantic states, no hardcoded hex)
- Added `/reviewDesignConventions` command — plan-mode design system review against `celer-06`
- Updated `explain-vigil` command descriptions

### Why

Design token migration (primary/surface/ink semantic palettes) needed enforceable rules separate from script/template conventions. A dedicated review command catches violations before PR.

### Files affected

- `.cursor/rules/celer-06-design-system.mdc`
- `.claude/rules/celer-06-design-system.md`
- `.cursor/rules/celer-01-vue-checklist.mdc`
- `.claude/rules/celer-01-vue-checklist.md`
- `.claude/commands/reviewDesignConventions.md`
- `.cursor/rules/reviewDesignConventions.mdc`
- `.claude/commands/explain-vigil.md` (via related commit)
- `.cursor/rules/explain-vigil.mdc` (via related commit)
- `app.vigil/src/styles/theme/RULES.md` (theme docs consolidated in same period)

---

## 2026-05-01 — `generateComponentRules` command and checklist update (app.vigil)

### What changed

- Added `/generateComponentRules` command and matching Cursor rule — prompts agents to write a `RULES.md` for any folder in `app.vigil/src/`
- Added canonical prompt at `cortex/generate-component-rules.md`
- Updated `celer-01-vue-checklist` step 1: check for `RULES.md` in folder chain before editing
- ESLint: same-name `v-bind` shorthand (`vue/v-bind-style`) and template attribute ordering updates

### Why

Folder rules need a repeatable generation workflow so new components get documented consistently. The checklist step ensures agents look for existing rules before writing code.

### Files affected

- `cortex/generate-component-rules.md`
- `.claude/commands/generateComponentRules.md`
- `.cursor/rules/generate-component-rules.mdc`
- `.cursor/rules/celer-01-vue-checklist.mdc`
- `.claude/commands/reviewVueConventions.md`
- `app.vigil/eslint.config.ts` (ESLint rule updates)

---

## 2026-04-30 — Unified `class` / `:class` binding rule (app.vigil)

### What changed

Added a CRITICAL rule to app.vigil template conventions: when an element has BOTH static `class` and bound `:class`, they MUST be unified into a single `:class` array. The static string becomes the first array entry; dynamic parts (objects, conditional strings, icon vars) follow.

Single-source attributes are unaffected — `class="..."` alone or `:class="..."` alone stay as is. The rule only triggers when both would otherwise coexist on the same element.

### Why

While refactoring `TheNavbar` into organism components, several `<a>` and `<span>` elements ended up with both `class="..."` and `:class="{ ... }"` after adding active-state styling. Splitting the binding made diffs noisier and made the static portion harder to scan against the dynamic conditions. Unifying into one `:class` array keeps the entire class surface in one place and matches how the rest of the codebase already handles mixed bindings.

### Files affected

- `.cursor/rules/celer-04-vue-template.mdc` — new "Unified class binding" section
- `.claude/rules/celer-04-vue-template.md` — same section, expanded examples
- `.cursor/rules/celer-01-vue-checklist.mdc` — checklist item + Quick Ref row
- `.claude/rules/celer-01-vue-checklist.md` — checklist item + Quick Ref row

---

## 2026-04-28 — Commands and cleanup additions

### Added `/create-pr` command

Generates a PR title and description from the current branch diff following shared commit conventions. Available in both Claude Code and Cursor. Output is copy-paste ready — no GitHub connection required.

**Files:** `.claude/commands/create-pr.md`, `.cursor/rules/create-pr.mdc`

### Removed redundant vitrum AI folders

`vitrum/.claude/` and `vitrum/.cursor/` deleted — with rules living at the monorepo root, per-module AI folders serve no purpose.

### `start-session.mdc` model check

Added a model check at the top of the Cursor `start-session` rule. If the active model is not premium, the AI will flag it and suggest switching before loading rules.

**File:** `.cursor/rules/start-session.mdc`

---

## 2026-04-28 — Full AI infrastructure implemented at monorepo root

### Decision: monorepo-always workflow

vigil projects are always started from the monorepo root. Individual modules (`api.vigil/`, `app.vigil/`, etc.) are never forked standalone. This eliminated the need for per-module AI tooling copies and for cortex rule sub-folders.

**Consequence:** All AI rules, skills, agents, commands, and hooks now live exclusively at the vigil root. Cortex no longer holds rule files — it holds history, prompts, and documentation.

### What was built

**Root `.claude/` structure:**
- `rules/` — 7 glob-routed rule files (api.vigil architecture, app.vigil Vue/TS × 5, shared conventions)
- `settings.json` — shared team permissions and hooks configuration
- `hooks/post-edit.sh` — runs Pint automatically after PHP edits in api.vigil/, ESLint after Vue/TS edits in app.vigil/
- `hooks/pre-bash-guard.sh` — blocks dangerous bash patterns
- `skills/arcus-laravel-best-practices/` — Laravel best practices (20 rule files)
- `skills/arcus-pest-testing/` — Pest 4 testing patterns
- `skills/arcus-tailwindcss/` — Tailwind v4 patterns
- `skills/celer-advanced-patterns/` — Advanced Vue 3 composable, store, and form patterns
- `agents/code-reviewer.md` — read-only PR review agent
- `agents/security-auditor.md` — security scan agent
- `commands/reviewVueConventions.md` — review app.vigil Vue/TS convention violations
- `commands/reviewArcusCode.md` — review api.vigil PHP convention violations
- `commands/sync-cortex.md` — sync cortex prompts to distribution targets

**Root `.cursor/` structure:**
- `rules/project-context.mdc` — always-loaded Cursor equivalent of CLAUDE.md, includes rule router for planning sessions
- `rules/shared-conventions.mdc` — always-loaded git/commit/PR conventions
- `rules/arcus-api-architecture.mdc` — scoped to `api.vigil/**`
- `rules/celer-01` through `celer-05.mdc` — scoped to `app.vigil/**/*.vue, app.vigil/**/*.ts`

**Root level:**
- `CLAUDE.local.md` — gitignored personal overrides template
- `.gitignore` updated to exclude `CLAUDE.local.md` and `.claude/settings.local.json`
- `CLAUDE.md` updated with full AI tooling reference and session start instructions

**Cortex:**
- `start-session.md` — copy-paste planning prompt for Claude and Cursor
- Rule sub-folders (`vue-rules/`, `laravel-rules/`, `astro-rules/`) deleted

### Glob routing

Each rule activates only when the edited file path matches:

| Rule file | Activates for |
| --- | --- |
| `arcus-api-architecture.md` | Any file in `api.vigil/**` |
| `celer-01` through `celer-05` | `app.vigil/**/*.vue`, `app.vigil/**/*.ts` |
| `shared-conventions.md` | Always (alwaysApply: true) |

### Planning session gap

During planning (no files open), glob rules don't activate. Solved two ways:
1. `project-context.mdc` in Cursor rules instructs the AI to proactively load the right rules when you name the project
2. `cortex/start-session.md` provides a manual copy-paste prompt for both Claude and Cursor

---

## Template for future entries

```markdown
## YYYY-MM-DD — Title

### What changed
- ...

### Why
- ...

### Files affected
- ...
```
