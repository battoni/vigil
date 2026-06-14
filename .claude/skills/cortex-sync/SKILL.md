---
name: cortex-sync
description: "Metadata and propagation discipline for vigil-derived repos. Load when editing or creating a unit that has (or should have) a RULES.md + CHANGELOG.md, when bumping a component/view/module/service version, when running or reasoning about the sync engine (sync.py, the vigil-upstream vendor branch, /setup-project), or when touching the manifest, transform, or any .cortex-* file."
license: MIT
metadata:
  author: vigil
---

# Cortex Sync — Metadata & Propagation Discipline

> **Scope:** any vigil-derived repo (vigil itself, or a project bootstrapped from it).
> Keeps every agent following the per-unit metadata + propagation rules so improvements flow
> vigil → projects cleanly. Depth lives in `cortex/sync/DESIGN.md` (why), `SCHEMA.md` (the
> metadata contract), and `RUNBOOK.md` (how to run a sync). This skill is the **trigger plus
> the rules** — not a copy of those docs.

## When this applies

- Editing or creating a **unit** — the nearest folder that has (or should have) a co-located
  `RULES.md` + `CHANGELOG.md` (component, view, module, API module, or a file-group unit such
  as build config).
- Changing a component / view / module / service and needing to **bump its version**.
- Running or reasoning about the sync engine (`sync.py`, the `vigil-upstream` vendor
  branch, `/setup-project`).
- Touching the manifest, the transform, or any `.cortex-*` file.

## Triad discipline

A unit = **source + `RULES.md` + `CHANGELOG.md`**. They travel together — never change a
unit's code without its metadata, and never move or copy a unit without both files.

## Version on change (blocking)

Any change to a unit's code **or its tests** → a **new top `CHANGELOG.md` entry** + a matching
`version:` in the `RULES.md` frontmatter.

- **CHANGELOG is canonical**; the frontmatter mirrors its top version. The two must be equal.
- SemVer: **major** = breaking contract · **minor** = additive · **patch** = tweak/fix.
- A **test-only** change still bumps the unit (patch or minor) — tests are part of the unit
  (app.vigil: co-located `*.spec.ts`; api.vigil: mirrored under `tests/`).
- In **vigil**, `based-on` mirrors `version` (a unit is its own origin) — **bump both
  together**. A stale `based-on` (`< version`) makes the sync triage read the unit as falsely
  **diverged** downstream.
- Full schema: `cortex/sync/SCHEMA.md`.

## MODE awareness

Every path carries a sync MODE (see the manifest):

| MODE | What | Sync behavior |
| --- | --- | --- |
| OVERWRITE | AI layer (rules, skills, commands, agents) | vigil replaces downstream |
| MERGE | code, tests, config | vendor branch → human merges; never a silent overwrite |
| LOCAL | brand / identity (theme, names, env) | downstream owns it; never touched |
| SKIP | generated / vendored / gitignored | ignored |

**Meta-tests** (architecture / convention guardrails) are **vigil-only** — they do not
propagate downstream.

## Naming & transform

When the engine ports a file downstream it rewrites tokens: the project-word map
(`app.vigil` → the project's app word, etc.) rewrites identifiers, **`celer-` / `arcus-` filename
tokens are protected** (never renamed), and **MSW / e2e URL tokens** transform too. Don't
hand-rename across this boundary — let the transform own it.

## Parallel-session safety

**Stage by explicit path. Never `git add -A`.** Multiple sessions may touch the tree at once;
a blanket add corrupts another session's staging. This holds for every commit, not just sync.

## Source-of-truth direction

Improvements flow **vigil → projects**, one way. A fix made directly in a downstream
project is **back-ported by manual cherry-pick** into vigil — the sync never reads
downstream changes back automatically.

## Don't duplicate the docs

For the *why* read `DESIGN.md`, for the metadata *contract* read `SCHEMA.md`, for *running a
sync* read `RUNBOOK.md` (all under `cortex/sync/`). Keep this skill a pointer, not a copy.
