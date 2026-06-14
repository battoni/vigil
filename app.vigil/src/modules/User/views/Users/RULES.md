---
version: 1.4.0
origin: vigil
based-on: 1.3.0
---

# RULES — UsersView

> For AI agents. Last updated: 2026-05-22.

## Purpose

User management page: filterable/sortable grid of `MUserCard` components, add/edit dialog, and archive/delete confirm popups.

## Intentional Decisions

- **`allUsersForSearch` is separate from `users`**: `allUsersForSearch` always holds the full user list (never filtered); `users` holds the currently displayed subset. When `ids == null` in `loadUsers`, both are updated. Search/filter only updates `users`.
- **`useTemplateRef<{ hide: () => void }>`** for `searchMultiselectRef`: the MultiSelect is hidden programmatically after search submit/cancel. The template ref type is explicitly typed for the `hide()` method.
- **Search uses `number[]` for user IDs**, not strings: `search` ref is `number[]` and `searchOptions` maps `optionValue` to `user.id` (number). The `loadUsers({ ids })` call passes these to the API.
- **`onArchiveAccept` / `onDeleteAccept` are local function declarations inside their parent handlers**: this is intentional — they close over the current user/state without needing additional params. Do not extract them to the top level.
- **`onDialogSuccess` does granular list update**: prepends new users; replaces existing users by ID. No full refetch.
- **Archive/delete use raw `ConfirmPopup` (not `MConfirmPopup`)**: the view inlines `confirm.require({ ... })` with custom `acceptProps`/`rejectProps` classes instead of using `MConfirmPopup`. Both groups (`'archive'`, `'delete'`) have matching `<ConfirmPopup>` elements in the template.
- **Inline skeleton grid**: the loading skeleton is inlined in the view template, not a separate skeleton component (unlike `MRolesAndPermissionsSkeleton`).
- **`canCreate` / `canRead` computed from `userStore.hasPermission`**: permission checks happen here, not in `MUserCard`.

## Dependencies & Context

- **`MOrderBy`**: bound with `v-model:orderBy`, triggers `onOrderByChange` watch which reloads with current filter.
- **`MMainDialog`** is keyed on `editingUser?.id ?? 'new'` to reset the form.

## Do Not

- **Do not add a dedicated skeleton component** unless the Users list skeleton is reused elsewhere — the inline skeleton is intentional for simplicity.
- **Do not merge archive/delete confirm groups** — they show different messages and action labels.
