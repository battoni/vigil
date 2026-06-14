---
title: /explain-vigil
outline: deep
---

::: info Generated page
This page is generated from `.claude/commands/explain-vigil.md` — run it with `/explain-vigil`. **Edit the source command, not this page.**
:::

# Explain vigil

Give the developer a complete overview of vigil's AI infrastructure — what exists, how it works, and how to use it day to day.

## Step 1 — Read the source files

Read these files before explaining anything:

- `CLAUDE.md`
- `cortex/README.md`
- `cortex/CHANGELOG.md`

## Step 2 — Deliver the explanation

Structure the explanation as follows. Be concise — one clear paragraph or table per section. No padding.

---

### What vigil is

What the monorepo contains, what each sub-project does, and the core workflow principle (monorepo-always — always work from root, delete unused modules, rename folders for each project).

### How the AI tooling is structured

Explain the two layers:

- **Rules** (`.claude/rules/` and `.cursor/rules/`) — activate automatically by file path via glob patterns. No manual loading during implementation.
- **Context** (`CLAUDE.md` and `project-context.mdc`) — always loaded, gives the AI project awareness from the first message.

Explain the planning gap and how `/start-session` solves it.

### Available commands

A table of every command, what it does, and when to use it:

| Command | When to use |
| --- | --- |
| `/setup-project` | Once, right after cloning and renaming folders for a new project |
| `/start-session` | Every session — loads the right rules before planning begins |
| `/reviewVueConventions` | Before opening a PR that touches app.vigil — checks Vue/TS convention violations |
| `/reviewDesignConventions` | Before opening a PR that touches app.vigil — checks design system violations (colors, typography, spacing, radius, buttons) |
| `/reviewArcusCode` | Before opening a PR that touches api.vigil |
| `/generateComponentRules` | When adding a new component folder — generates a `RULES.md` with folder-specific AI instructions |
| `/create-pr` | When ready to open a PR — generates title, description, and pre-merge checklist |
| `/explain-vigil` | This command — explains everything to a new developer |

### Rules in detail

Which rules exist, what they cover, and when each one activates. Include the shared conventions rule (always loaded).

### Skills, agents, and hooks

- **Skills** — what each skill covers and when it auto-loads
- **Agents** — what `code-reviewer` and `security-auditor` do and how to invoke them
- **Hooks** — what runs automatically after file edits (Pint for PHP, ESLint for Vue/TS) and what the bash guard blocks

### Starting a new project

The exact steps from clone to first session:

```
1. Clone vigil
2. Delete unused modules
3. Rename folders (e.g. api.vigil → api.myproject, app.vigil → app.myproject)
4. Run /setup-project
5. Run /start-session
```

### Day-to-day workflow

```
Start of session:     /start-session
During development:   rules load automatically as you edit files
Before a PR:          /reviewVueConventions and/or /reviewArcusCode and/or /reviewDesignConventions
Opening a PR:         /create-pr
```

### How to add or update a rule

Edit the rule file directly in `.claude/rules/` (Claude) and `.cursor/rules/` (Cursor). Add an entry to `cortex/CHANGELOG.md`. No syncing needed — there is only one copy of each rule.

---

## Step 3 — Offer next steps

End with: "What would you like to start with?"
