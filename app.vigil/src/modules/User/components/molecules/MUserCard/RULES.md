---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — MUserCard

> For AI agents. Last updated: 2026-05-22.

## Purpose

Card representation of a user with avatar, role tag, status tag, full name, and action buttons (edit, archive/activate, delete).

## Intentional Decisions

- **`isInactive` checks for both `'inactive'` and `'inativo'`**: supports both English and Portuguese status strings from the backend.
- **Delete button only appears when `isInactive`**: deleting an active user is not allowed by design; the button is conditionally rendered, not just disabled.
- **Top border color driven by `isInactive`**: `border-primary-600` (active) vs `border-danger-600` (inactive) via `:class` binding on the `Card`. The `border-t-16` class is a custom token for the thick top accent border.
- **`initials` computed**: first letter of `name` + first letter of `last_name`. Used as `Avatar` label when no `profile_picture` is set.
- **Action buttons use `celer-button-text-*` custom classes**: `celer-button-text-info`, `celer-button-text-warn`, `celer-button-text-danger`. These are global utility classes — do not replace with inline `text-*` color classes.

## Prop & Emit Contract

- `user`: the `User` entity to display.
- `canEdit` / `canArchive` / `canDelete`: permission guards. Each hides its respective button when `false`. Defaults to `true` (show all).
- `@onEditUserRequest`: user clicked Edit. No payload.
- `@onArchiveUserRequest([event])`: user clicked Archive/Activate. Event needed for popup anchor.
- `@onDeleteUserRequest([event])`: user clicked Delete. Event needed for popup anchor.

## Do Not

- **Do not add delete button for active users** — only show when `isInactive`.
- **Do not normalize status strings** inside this component — keep the `'inativo'` check for backend compatibility.
