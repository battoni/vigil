---
title: /pre-commit
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/pre-commit.md` — run it with `/pre-commit`. **Edit the source command, not this page.**
:::

# Pre-commit

Run every mechanical and convention check **after** the developer has finished their manual review, but **before** committing. This command commits nothing — it only verifies the branch is ready.

> Run from the vigil monorepo root.

---

## Step 1 — Mechanical checks

Run the Makefile gate, which bundles lint, format, locale sorting, and the full test suite:

```bash
make pre-commit
```

This runs, in order:

1. `make sanitize` — `npm run lint`, `npm run format`, `npm run sort-locales` in app.vigil
2. `make test-all` — app.vigil unit/integration (`test:unit`), app.vigil e2e (`test:e2e`), api.vigil `pest`

If any step fails, **stop** and report what failed. Do not continue to the reviews until the tree is green.

## Step 2 — Convention reviews

With the mechanical checks green, run the AI convention reviews for whichever sub-projects the branch touched:

- If the branch changed any `app.vigil/**` Vue/TS files → run `/reviewVueConventions`, then `/reviewDesignConventions`.
- If the branch changed any `api.vigil/**` PHP files → run `/reviewArcusCode`.

Use `git diff main...HEAD --stat` (or the project's base branch) to decide which reviews apply.

## Step 3 — Report

Summarise the outcome:

- ✅ / ❌ for `make pre-commit`
- ✅ / ❌ for each review that ran, with any violations found and fixed
- A clear go / no-go: is the branch ready for `/commit`?

---

## Rules

- **Never commit, stage, or push** — this command only verifies. Committing is `/commit`.
- If lint/format/locale steps modified files, leave them in the working tree for `/commit` to pick up — do not commit them here.
- If a review finds violations, fix them, then re-run the relevant checks before reporting go.
- Stop on the first failing mechanical step; do not mask failures.
