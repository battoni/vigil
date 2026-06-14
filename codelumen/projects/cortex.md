---
outline: deep
title: Cortex
---

# Cortex — AI Knowledge Layer

Cortex is the **knowledge layer** of vigil's AI infrastructure. It holds the history of decisions, reusable prompts, and documentation about the overall AI setup.

Rules themselves are **not** stored here — they live at the monorepo root (`.claude/rules/`, `.cursor/rules/`) and activate automatically by file path. Cortex is the "why and history" around them.

## What lives in Cortex

| File | Purpose |
| --- | --- |
| `CHANGELOG.md` | History of every AI infrastructure decision and change |
| `README.md` | Overview of the AI setup |
| `generate-component-rules.md` | Prompt: generate a `RULES.md` for a component folder |
| `hooks/` | Hook scripts (rule injection, formatting) |
| `sync/` | Sync tooling for distributing rules to target projects |

## Design principles

- **Monorepo-always** — vigil is used as a whole; modules are never forked standalone.
- **Single source of truth** — each rule exists in exactly one place; no copies, no drift.
- **Glob routing** — rules activate automatically by file path.
- **Planning gap** — `/start-session` loads the right rules before any files are open.

## See also

The full, day-to-day view of what Cortex provides — rules, skills, agents, hooks, commands — is on the [AI Tooling](/ai-tooling) page.
