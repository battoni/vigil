---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — MAddEditUserForm

> For AI agents. Last updated: 2026-05-22.

## Purpose

Add/Edit form for a User entity. Handles create and update modes, async username availability check, permission override via MultiSelect, and conditional password reset.

## Intentional Decisions

- **Hidden dummy inputs at the top of the form**: `<input type="text" autocomplete="username" tabindex="-1">` and `<input type="password" autocomplete="current-password" tabindex="-1">` exist solely to satisfy browser autofill heuristics and prevent Chrome from auto-filling the real username/password fields. Do not remove them.
- **Username check debounce**: `scheduleUsernameCheck` fires 400ms after each keystroke via `setTimeout`. `shouldValidateUsername` is set to `true` only after the first focus on the username field, preventing the check from running on initial render.
- **`formResetKey`** forces full Form remount in `onComponentMount`: PrimeVue Forms doesn't natively reset when `initialValues` change, so the key increment is required.
- **`passwordReadonly: true` until focused**: on edit mode, the password field starts read-only to prevent browser autofill from injecting the old password. It becomes editable on `@focus`.
- **Role is controlled via a separate `selectedRoleName` ref + hidden `<input name="role">`**: PrimeVue `Select` does not bind its value through PrimeVue Forms automatically; the hidden input bridges the gap so `values.role` is available on submit.
- **`canResetPassword` gates the password field**: in edit mode, the password field is disabled if the session user doesn't have `users.reset_password` permission. A watch on `canResetPassword` resets the form if permission is revoked while editing.
- **Permissions are submitted as `Record<string, boolean>`** (all known keys, with `true`/`false`): `buildPermissionsRecord` constructs the full record from `permissionGroups`, not just the selected ones.
- **`onSuccess` receives the updated `User` from the API**: the parent view does a granular replace, not a full refetch.
- **Submit button is outside the `<Form>`**: uses `form="user-form"` to trigger submit, same pattern as `MAddEditProfileForm`. Required for the sticky footer in `MMainDialog`.

## Prop & Emit Contract

- `user`: the `User` to edit, or `null`/`undefined` for create mode. **Key on `user?.id ?? 'new'`** at the call site.
- `@onClose`: Cancel was clicked. Parent closes the dialog.
- `@onSuccess([user])`: successful save; parent updates the list granularly.

## Edge Cases Handled

- **Editing the current session user**: if `data.id === userStore.user.id`, `fetchMe()` is called to refresh session permissions.
- **`usernameError` blocks submit**: if the username check found a conflict, `hasFormError` prevents the API call.

## Do Not

- **Do not remove the hidden dummy inputs** — browser autofill breaks without them.
- **Do not call `GetUsersService` inside this component** — user list is managed by the parent view.
