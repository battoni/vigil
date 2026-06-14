---
title: /setup-project
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/setup-project.md` — run it with `/setup-project`. **Edit the source command, not this page.**
:::

# Setup Project

Initialize a new project cloned from vigil: transform the bootstrap into your project's
names and trim what you don't use. Run this once, right after cloning and `git init`.

**This command runs in plan mode. Do not edit any file until the plan is approved.**

---

## Step 1 — Gather project info

Ask the user the following, one at a time:

1. **Project name** — a lowercase slug (e.g. `zion`, `acme`). This becomes `PROJECT`; the
   frontend becomes `app.<project>` and the API `api.<project>`.
2. **Frontend (`app.vigil`)** — used? (If yes it is renamed to `app.<project>`.)
3. **API (`api.vigil`)** — used? (If yes it is renamed to `api.<project>`.)
4. **Other sub-projects** — `codelumen` (docs), `vitrum` (site), `liquen` (design tokens):
   keep or remove each? (These keep their own names — the transform only renames app.vigil/api.vigil.)
5. **Feature modules to remove** — the bootstrap ships app.vigil modules `Auth`, `User`, `Home`,
   `Showcase` (and api.vigil `Auth`). List any you don't want; each gets deleted **and its full
   test surface pruned** (Step 2c).

---

## Step 2 — Build the plan

Produce a plan with three parts. Present it and **wait for approval** before changing anything.

### a. Config + transform (the shared engine does the renaming)

- Create `.cortex-sync.conf` at the repo root — this is what every later `cortex/sync` run reads:

  ```
  PROJECT=<project>
  APP=app.<project>
  API=api.<project>
  ```

- Run the shared transform engine. It renames `app.vigil/`→`app.<project>/` and
  `api.vigil/`→`api.<project>/`, and rewrites every `app.vigil`/`api.vigil`/`vigil` word in **file
  contents** — rule globs, `CLAUDE.md`, `project-context`, command paths, hook patterns, all of
  it — while preserving `celer-`/`arcus-` rule **filenames**. No more editing globs by hand:

  ```bash
  python3 cortex/sync/sync.py --init --dry-run   # preview the renames + file count
  python3 cortex/sync/sync.py --init             # apply
  ```

### b. Remove unused sub-projects

For each unused sub-project (`codelumen`, `vitrum`, `liquen` — or `app.vigil`/`api.vigil` themselves if
not used), delete:

- the sub-project folder,
- its `.claude/rules/*` and `.cursor/rules/*` files,
- its `.claude/skills/*` and `.claude/commands/*` (e.g. `reviewArcusCode.md` if api.vigil is dropped),
- its line(s) in `.claude/commands/start-session.md` and `.cursor/rules/start-session.mdc`.

### c. Remove unused feature modules — prune the FULL test surface

This is the easy-to-miss part: a module's co-located specs vanish when you delete its folder,
but things **outside** the folder still reference it. For each removed module `<X>` (e.g.
`Showcase`):

- delete `app.<project>/src/modules/<X>/` (co-located unit + integration specs go with it), then prune:
  - `app.<project>/vitest.config.ts` — the `moduleSubAliases` entry (`@<X>Module`),
  - `app.<project>/playwright.config.ts` — any per-module project entry,
  - `app.<project>/src/test/msw/handlers.ts` — the module's mock endpoints,
  - `api.<project>/tests/Feature/Modules/<X>/` — its api.vigil feature tests (if any),
  - `app.<project>/e2e/<x>/` — its e2e specs (if any),
  - its `.claude/rules` / `.cursor/rules` folder-glob entries, if module-specific.

---

## Step 3 — Execute (after approval)

1. Write `.cortex-sync.conf` (Step 2a).
2. Run `python3 cortex/sync/sync.py --init`.
3. Delete unused sub-projects and their tooling (Step 2b).
4. Delete unused feature modules and prune their full test surface (Step 2c).
5. Sanity-check what remains:
   - `python3 cortex/sync/sync.py --audit` — every file still classifies,
   - `make unit` and `make pest` — green for the kept modules.

---

## Step 4 — Confirm

Report what was renamed, removed, and pruned. End with:

> Project configured as **&lt;project>**. `.cortex-sync.conf` is set, so future vigil updates
> flow in via `cortex/sync/RUNBOOK.md`. Run `/start-session` to begin.
