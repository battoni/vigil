# Generate RULES.md

You are an AI agent writing a `RULES.md` file for another AI agent to consume. This file must capture folder-specific rules that are **not derivable from the general conventions** or from reading the code alone.

A `RULES.md` can live in **any folder** — a component folder, a module folder, a views folder, etc. Agents receive the full chain automatically on Edit/Write via the pre-edit hook (`cortex/hooks/inject-rules-context.py`). Closer files take higher priority. On Read/planning, read all `RULES.md` files between the file and `src/` manually.

## Instructions

1. Read the target folder (all files in it: `.vue`, `.ts`, composables, types, etc.)
2. Read all app.vigil rule files (`.claude/rules/celer-01` through `celer-05`) to understand the global rules that already apply to every Vue/TS file — **do not repeat anything covered there**
3. Analyze and extract only what is **specific, non-obvious, or intentional** about this folder's scope
4. Write a `RULES.md` file inside the target folder

---

## What to capture in RULES.md

Only write rules if they answer one or more of these questions:

- **What is this component's single responsibility?** (1–2 sentences max)
- **What are the intentional design decisions** that look wrong but aren't? (e.g., "the delete button is only shown when inactive — this is intentional")
- **What must never be changed** without understanding the full context?
- **What are the prop/emit contracts** that carry semantic meaning beyond their types?
- **What are the edge cases** this component already handles?
- **What external dependencies** does this component have that aren't obvious from imports?

Do NOT write rules for:

- Things already covered in `cortex/vue-rules/` (imports, script structure, template formatting, module conventions, etc.)
- Things obvious from reading the code
- Generic Vue/TypeScript best practices
- Anything that could change freely without breaking the component's contract

---

## Output format

Write the `RULES.md` using this structure:

```md
# RULES — [ComponentName]

> For AI agents. Last updated: YYYY-MM-DD.

## Purpose
[One sentence: what this component does and its scope]

## Intentional Decisions
- [Decision]: [Why it exists]

## Prop & Emit Contract
- `propName`: [What it semantically means, not just its type]
- `emitName`: [When it fires and what the consumer is expected to do]

## Edge Cases Handled
- [Case]: [How it's handled and why]

## Do Not
- [Specific thing to avoid and why]

## Dependencies & Context
- [Any non-obvious runtime dependency, store, composable, or parent contract]
```

Omit any section that has nothing meaningful to say. Do not pad with filler.

---

## Target

Point me to the folder and I will generate the `RULES.md`.
