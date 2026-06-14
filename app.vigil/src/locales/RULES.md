---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# Locale File Conventions

## Structure

Every locale file (`en.json`, `pt-BR.json`, etc.) follows a three-tier grouping at the **top level**:

1. **COMMON** — generic, cross-cutting translations (`common`, `errors`)
2. **COMPONENT** — translations owned by a specific shared component (`navigation`)
3. **MODULE** — feature module translations (`auth`, `finance`, `rolesAndPermissions`, `users`)

Groups appear in this order. Keys are sorted **alphabetically within each group**.

```
common      ← COMMON
errors      ← COMMON
navigation  ← COMPONENT (TheNavbar)
auth        ← MODULE
finance     ← MODULE
rolesAndPermissions ← MODULE
users       ← MODULE
```

When adding a new top-level key, also register it in `scripts/sort-locales.mjs` under `TOP_LEVEL_GROUPS`.

---

## Key Order Within Every Object

Within any nested object, at every level:

1. **One-liners first** — keys whose value is a `string`, sorted **ASC**
2. **Multi-liners second** — keys whose value is a nested `object`, sorted **ASC**

```json
{
  "cancel": "Cancel",
  "save": "Save",
  "title": "My Title",
  "actions": { "delete": "Delete", "edit": "Edit" },
  "form": { "name": "Name" }
}
```

This applies **recursively** — every nested object follows the same rule.

---

## Automation

`npm run sort-locales` re-sorts all locale files according to these rules. Run it after any structural change (adding keys, renaming, moving).

**ESLint** (`npm run lint`) catches duplicate keys and invalid JSON numbers but does **not** enforce sort order — `jsonc/sort-keys` only knows alphabetical and cannot express the one-liners-first tier or the COMMON/COMPONENT/MODULE grouping.

---

## Adding a New Key

1. Add the key in the correct position manually, or just run `npm run sort-locales` after adding it anywhere.
2. Add the same key to **all** locale files.
3. If adding a new top-level section, register it in `scripts/sort-locales.mjs`.
