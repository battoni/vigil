---
title: /start-session
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/start-session.md` — run it with `/start-session`. **Edit the source command, not this page.**
:::

# Start Session

Load the right rules before planning or working on a feature.

## Step 1 — Ask which projects

Ask the user: "Which project(s) are you working on — api.vigil, app.vigil, codelumen, vitrum, liquen, or cortex?"

## Step 2 — Load rules

**api.vigil** — read `.claude/rules/arcus-api-architecture.md`

**app.vigil** — read all five files:

- `.claude/rules/celer-01-vue-checklist.md`
- `.claude/rules/celer-02-vue-imports.md`
- `.claude/rules/celer-03-vue-script.md`
- `.claude/rules/celer-04-vue-template.md`
- `.claude/rules/celer-05-module-conventions.md`

**codelumen, vitrum, liquen, cortex** — no rules configured yet. Acknowledge this to the user and proceed without loading rules.

## Step 3 — Confirm

Reply with a single line confirming what was loaded. For example:

> Loaded api.vigil + app.vigil rules. Ready — what are we building?

Or if a project has no rules yet:

> No rules configured yet for vitrum. Ready — what are we building?
