---
version: 1.2.0
origin: vigil
based-on: 1.2.0
---

# RULES — RolesAndPermissionsView

> For AI agents. Last updated: 2026-05-22.

## Purpose

Full role management page: tabbed role list, permission panels, add/edit role dialog, and delete confirm popup.

## Intentional Decisions

- **`panelStates` record** (`Record<string, boolean>`): keyed by permission group ID, stores the collapsed state of each `MPermissionPanel`. This is intentional — collapsed state is tracked per-group, not per-panel component instance.
- **`panelResetKey`** is incremented to force-remount `MPermissionPanel` instances: PrimeVue `Panel` does not reset collapsed state when its `v-model:collapsed` prop changes externally. The key forces a full remount to apply the new state.
- **`allPanelsExpandedModel`** is a writable computed that sets all `panelStates` values: the getter checks all states; the setter applies a boolean to all. This drives `MPermissionToolbar`'s expand/collapse switch.
- **`setPermissionGroups` initializes `panelStates`** to all `true` (collapsed): called on initial load and after role create/edit/switch.
- **Two `ConfirmDialog` / `ConfirmPopup` groups**: `'delete'` uses `MConfirmPopup` for the standard delete confirm; `'delete-error'` uses PrimeVue's `ConfirmDialog` to show users currently assigned to the role (cannot delete). Do not collapse them into one group.
- **`onSavePermissionsRequest` calls `userStore.fetchMe()`** after save: the current user's permissions may have changed if they edited their own role.
- **`MAddEditProfileForm` is keyed on `editingProfile?.id ?? 'new'`**: forces form reset when switching between create/edit modes.
- **`activeRole` is computed from `activeTab`** (the tab value = role ID): do not store `activeRole` as a separate ref.
- **`isFooterless` on `MMainDialog`**: the form has its own sticky footer buttons; no default Cancel/Submit footer needed.

## Dependencies & Context

- **`interfaces.ts`** in this folder defines `PermissionGroup` and `Profile` types used throughout the view and child components.
- **`MPermissionPanel`** mutates `permissionGroup.permissions[n].value` directly — the `permissionGroups` ref in this view IS the source of truth.

## Do Not

- **Do not add a second source for permission state** — mutations flow through `permissionGroups.value` directly.
- **Do not merge the two ConfirmDialog groups** — `delete-error` shows user list text and has different actions.
