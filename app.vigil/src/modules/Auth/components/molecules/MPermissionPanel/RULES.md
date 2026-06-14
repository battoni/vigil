---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — MPermissionPanel

> For AI agents. Last updated: 2026-05-22.

## Purpose

A collapsible Panel showing all permissions in a single `PermissionGroup`, with individual toggle switches and a "select all / deselect all" toggle.

## Intentional Decisions

- **`permissionGroup.permissions` items are mutated directly** via `permission.value = value` in the `allSelected` setter: the permissions array is passed by reference from the parent view's reactive state. This direct mutation is intentional — the view's `permissionGroups` ref is the single source of truth.
- **`allSelected` is a writable computed**: the getter checks if every permission is active; the setter applies a boolean to all. This drives the "activate/deselect all" ToggleSwitch.
- **`collapsed` defaults to `true`**: panels start collapsed. The parent controls collapse state via `panelStates` record and passes it as `v-model:collapsed`.
- **`allPanelsExpanded` prop** drives the ToggleSwitch tooltip text but does NOT control collapse: collapse is managed by `v-model:collapsed` from the parent. `allPanelsExpanded` is read-only context.
- **`PermissionGroup` type is local to `RolesAndPermissions/views`**: imported via relative path from `../../../views/RolesAndPermissions/interfaces`. This type is NOT in the module barrel — do not try to import it from `@AuthModule`.

## Prop & Emit Contract

- `permissionGroup`: the group to render, including its `permissions` array (items have a mutable `value` field).
- `allPanelsExpanded`: read-only boolean for tooltip context. Does not control collapse.
- `loading`: disables all toggle switches.
- `v-model:collapsed` (via `defineModel`): controls the panel's collapsed state.

## Do Not

- **Do not copy permissions before passing** — the parent expects direct mutation.
- **Do not import `PermissionGroup` from `@AuthModule`** — it's not exported from the barrel; use the relative path.
