---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — MPermissionToolbar

> For AI agents. Last updated: 2026-05-22.

## Purpose

Toolbar row above the permission panels for a role tab. Controls expand/collapse all, and surfaces save, edit, and delete actions.

## Intentional Decisions

- **`v-model` (no name) controls expand/collapse all**: the model is a `boolean` — `true` = all expanded, `false` = all collapsed. The parent view owns this state.
- **Delete is disabled when `canDeleteProfile` is `false`**: the administrator role cannot be deleted. A tooltip explains why.
- **Delete passes `event` (not just the click signal)**: `@click="(event) => emit('onDeleteProfileRequest', event)"` — the event is needed by the parent to position the `ConfirmPopup` anchor.
- **`tab` prop is the role ID**: used only to generate a unique `id` for the expand/collapse ToggleSwitch (`toggle-all-panels-${tab}`). Prevents `id` collisions when multiple tabs render simultaneously.

## Prop & Emit Contract

- `v-model` (boolean): expand/collapse all state. Two-way binding with the parent view.
- `loading`: disables all buttons and the expand/collapse switch.
- `tab`: role ID string, used only for unique element IDs.
- `canDeleteProfile`: `false` disables the delete button and updates its tooltip.
- `@onSavePermissionsRequest`: fires when Save is clicked.
- `@onEditProfileRequest`: fires when Edit is clicked.
- `@onDeleteProfileRequest([event])`: fires when Delete is clicked; passes the click event for popup anchoring.

## Do Not

- **Do not remove the `event` argument from the delete button handler** — the parent needs it for `ConfirmPopup` positioning.
