---
description: Shared conventions for the vigil monorepo — git, commits, PRs, code hygiene
globs: []
alwaysApply: true
---
# Shared Conventions

These rules apply across all sub-projects in this monorepo.

## Git Commit Messages

- Use imperative mood: `Add feature`, `Fix bug`, `Remove unused code` — not `Added`, `Fixed`, `Removed`
- Keep the subject line under 72 characters
- No period at the end of the subject line
- Separate subject from body with a blank line when a body is needed
- Body explains the *why*, not the *what*

## Branch Naming

- `feature/short-description` — new functionality
- `fix/short-description` — bug fix
- `refactor/short-description` — code change without behaviour change
- `chore/short-description` — tooling, dependencies, config

## Pull Requests

- One concern per PR — do not mix features, fixes, and refactors
- PR title follows the same imperative rules as commit messages
- Include a description that explains *why* the change is needed, not just what changed
- Keep PRs small enough to review in one sitting

## Code Hygiene

- No `console.log`, `console.error`, or `console.warn` in committed code
- No commented-out dead code — delete it, git history preserves it
- No TODO comments without a ticket reference
- Remove unused variables, imports, and functions before committing
