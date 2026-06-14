---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — OListStatement

> For AI agents. Last updated: 2026-06-05.

## Purpose

A generic bank-statement organism: two-line rows separated by thin dividers — title + badge/subtitle on the left, amount + status on the right. Works for any data shape — the parent maps data keys to visual roles via props.

## Props Contract

| Prop                                   | Required | Description                                                                                                                                                                                        |
| -------------------------------------- | -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `items`                                | ✓        | Array of plain objects — any shape                                                                                                                                                                 |
| `title`                                | ✓        | `{ valueKey }` — primary text (top-left)                                                                                                                                                           |
| `amount`                               | —        | **Optional.** `{ valueKey, valueFormatter?, align? }`. When omitted the row renders cleanly. `valueFormatter(raw) => string` formats non-money values; `align: 'left' \| 'right'` (default right). |
| `badge`                                | —        | `{ valueKey, toneKey }` — tag beside the subtitle                                                                                                                                                  |
| `status`                               | —        | `{ valueKey, toneKey }` — right status tag                                                                                                                                                         |
| `statusIconMap`                        | —        | Maps status tone value → `pi` icon class                                                                                                                                                           |
| `subtitle`                             | —        | `{ valueKey }` — muted text beside the badge                                                                                                                                                       |
| `itemKey`                              | —        | Key field for `v-for`; default `'id'`                                                                                                                                                              |
| `showDetails`                          | —        | Eye button per row → opens the detail dialog                                                                                                                                                       |
| `canEdit` / `canArchive` / `canDelete` | —        | Default `false`. Render the matching gated inline action button (pencil / inbox / trash)                                                                                                           |

## Events

- `@onEdit(item)` / `@onArchive(item)` / `@onDelete(item)` — emitted by the gated action buttons (inline **and** from the detail dialog) with the row's item.

## Slots

- `#details="{ item }"` — read-only detail content rendered inside the eyeball `MMainDialog`. Optional; the dialog is empty without it.
- `#actions="{ item, close }"` — custom per-row action buttons. Rendered **both** inline in the row **and** in the detail dialog footer, each with the correct `item`. `close()` dismisses the eyeball dialog — call it from an action triggered there.

## Eyeball dialog

The eye opens `MMainDialog` showing `#details`. **All actions — built-in `can*` buttons and the custom `#actions` slot — are mirrored into the dialog footer.** Built-in actions close the dialog before emitting; custom `#actions` run the parent handler and can call the slot's `close()` to dismiss it. With no actions, the dialog is `isFooterless` (read-only).

## Intentional Decisions

- **`amount` is generic, not a money hero** — optional, with `valueFormatter`/`align`. Financial usage (`{ valueKey }` only) is unchanged (back-compat).
- **Actions mirror inline ↔ dialog** — write a custom action once in `#actions`; it appears both inline and in the eyeball. Built-in `can*` buttons behave the same.
- **`tagClassByTone` is internal** — `error`/`warning` alias to `danger`/`warn`.

## Do Not

- **Do not add sorting or filtering** — display only; the parent prepares data (search `MultiSelect` + `MOrderBy` at the view level).
- **Do not hardcode tone/icon values** — drive via `statusIconMap` and tone keys from the parent.
