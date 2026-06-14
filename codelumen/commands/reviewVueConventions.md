---
title: /reviewVueConventions
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/reviewVueConventions.md` — run it with `/reviewVueConventions`. **Edit the source command, not this page.**
:::

# Review Vue Conventions

> This command reviews Vue/TS files in **app.vigil/**. Run from the vigil monorepo root.

Analyze all app.vigil changes in the current branch against `main`, identify every convention violation, and fix them.

**This command runs in plan mode. Do not edit any file until the plan is approved.**

---

## Step 1 — Read the rules

Before doing anything else, read all five rule files:

- `.claude/rules/celer-01-vue-checklist.md`
- `.claude/rules/celer-02-vue-imports.md`
- `.claude/rules/celer-03-vue-script.md`
- `.claude/rules/celer-04-vue-template.md`
- `.claude/rules/celer-05-module-conventions.md`

## Step 2 — Get the diff

Run `git diff main...HEAD -- app.vigil/` and read every changed file in full.

## Step 3 — Find violations

Go through every added line in every changed file. Check each line against the rules you read. Note every violation: what it is, which rule it breaks, what the fix is.

**Test coverage:** a changed unit (`.vue` component, service, store, or composable with a bumped `CHANGELOG.md`) should have or gain a co-located spec — `*.spec.ts` for unit, `*.integration.spec.ts` for view HTTP flows. Flag any unit modified without a corresponding test as a violation. See the `celer-testing` skill for `mountWithPlugins` / `renderWithPlugins` patterns.

**Changelog/version gate (per-unit metadata — blocking).** For every **unit** whose code changed on the branch — the nearest enclosing folder that holds both `RULES.md` and `CHANGELOG.md` (component, view, module, or the `.config-rules` config-unit) — assert both:

1. **A new CHANGELOG entry exists.** If any non-metadata file in the unit changed (`.vue`, `.ts`, spec) but the unit's `CHANGELOG.md` has no new top entry on this branch, that is a violation. Detect it: a unit whose code changed but whose `CHANGELOG.md` is *not* in the diff (`git diff main...HEAD --name-only -- <unit>/CHANGELOG.md` is empty) fails the gate.
2. **Versions match.** The `version:` in the unit's `RULES.md` frontmatter must equal the top entry version in its `CHANGELOG.md`. A mismatch is a violation.

Flag each as `[A.6 — missing changelog]` or `[A.6 — version mismatch]` with the unit path and the expected fix (add the entry / align the frontmatter). These are **blocking, not cosmetic**: the cortex propagation triage reads these versions to decide how each unit merges downstream, so a missed bump or a mismatch corrupts the triage.

## Step 4 — Present the plan

Output a structured fix plan, grouped by file. Be specific — each item must name the rule and describe the exact change:

```markdown
### src/modules/Auth/components/MLoginEmail/MLoginEmail.vue
- Line 3: [02 — module imports] `@AuthModule/services` → `@AuthModule`
- Line 58: [04 — multiline tag] closing `>` must be on its own line; content on next indented line
- Line 61: [04 — Tailwind order] classes need Prettier pass

### src/modules/Auth/services/verifyCode.service.ts
- Line 1: [05 — service pattern] missing `export default async function`
```

If there are no violations, state **✅ No violations** and stop.

**Do not make any changes yet.** Wait for approval.

## Step 5 — Execute (after approval)

After the plan is approved, apply every fix listed. Work file by file. Do not introduce any changes beyond what is listed in the plan.

## Step 6 — Sort locales

After all fixes are applied, run:

```bash
cd app.vigil && npm run sort-locales
```

This re-sorts all locale files according to the COMMON/COMPONENT/MODULE grouping and one-liners-first convention. Always run it last, even if no locale files were changed — it is idempotent.
