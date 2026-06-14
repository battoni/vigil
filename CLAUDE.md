# vigil — Monorepo Context

This is the vigil monorepo. It contains multiple independent sub-projects and a shared AI knowledge layer.

## Sub-projects

| Project | Type | Description |
| --- | --- | --- |
| `api.vigil/` | Laravel API | Backend API bootstrap — starting point for new APIs |
| `app.vigil/` | Vue 3 SPA | Frontend bootstrap — starting point for new web apps |
| `codelumen/` | VitePress | Code conventions documentation site |
| `vitrum/` | Astro | Public/marketing website bootstrap |
| `liquen/` | Design tokens | Figma integration and design token management |
| `cortex/` | AI knowledge | Changelog, prompts, and AI infrastructure documentation |

## AI tooling

| Layer | What it does |
| --- | --- |
| `CLAUDE.md` (this file) | Always-loaded project context |
| `.claude/rules/` | Rules that auto-load by glob — api.vigil, app.vigil, shared |
| `.claude/skills/` | Demand-loaded specializations: `arcus-laravel-best-practices`, `arcus-pest-testing`, `arcus-tailwindcss`, `celer-advanced-patterns` |
| `.claude/commands/` | `/start-session`, `/reviewVueConventions`, `/reviewArcusCode` |
| `.claude/agents/` | `code-reviewer` (read-only PR review), `security-auditor` (security scan) |
| `.claude/hooks/` | Pint runs automatically after PHP edits in api.vigil/; ESLint after Vue/TS edits in app.vigil/ |
| `.cursor/rules/` | Cursor rules — same content in `.mdc` format, `project-context.mdc` always loaded |

## Starting a session

Run `/start-session` — it will ask which project(s) you're working on and load the right rules before anything else.

## Rule activation

- **During implementation** (files open): rules load automatically via glob patterns — no manual step needed.
- **During planning** (no files open yet): run `/start-session`.
