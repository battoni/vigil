---
title: RULES.md auto-injection for app.vigil — pre-edit hook loads folder RULES.md before Edit/Write
outline: deep
---

::: info Generated page
This page is generated from `.claude/rules/celer-08-rules-auto-injection.md` — **edit the source rule, not this page.** Activates for: `app.vigil/**/*.vue`, `app.vigil/**/*.ts`.
:::

# RULES.md Auto-Injection

Before every **Edit** or **Write** on Vue/TS files under `app.vigil/src/`, a pre-edit hook injects the full `RULES.md` chain from the target file's folder up to `app.vigil/src/`.

**Closer `RULES.md` files override parent rules.** Files outside `app.vigil/src/` are ignored.

## Hook locations

| Tool | Hook |
| --- | --- |
| Claude Code | `.claude/hooks/pre-edit-rules.sh` → `cortex/hooks/inject-rules-context.py` |
| Cursor | `.cursor/hooks/pre-edit-rules.sh` → same script |

## Agent obligations

1. **Follow injected RULES.md content** — it takes priority over generic conventions when they conflict.
2. **Folder-specific rules** in `.cursor/rules/celer-folder-*.mdc` also load when matching app.vigil files are open.
3. On **Read** or planning (no Edit/Write yet), manually read `RULES.md` in the folder chain if not already injected.
