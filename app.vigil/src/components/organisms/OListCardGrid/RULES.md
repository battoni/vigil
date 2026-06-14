---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — OListCardGrid

> For AI agents. Last updated: 2026-06-05.

## Purpose

A generic card-grid organism: items rendered as cards in a 1→2→3 responsive grid. Works for any data shape — the parent maps data keys to visual roles via props.

## Props Contract

| Prop                                   | Required | Description                                                                                                                                                                                                       |
| -------------------------------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `items`                                | ✓        | Array of plain objects — any shape                                                                                                                                                                                |
| `title`                                | ✓        | `{ valueKey }` — title line under the amount                                                                                                                                                                      |
| `amount`                               | —        | **Optional.** `{ valueKey, valueFormatter?, align? }`. When omitted the card renders cleanly. `valueFormatter(raw) => string` formats non-money values; `align: 'left' \| 'right'` (default left in this layout). |
| `badge`                                | —        | `{ valueKey, toneKey }` — top-left tag                                                                                                                                                                            |
| `status`                               | —        | `{ valueKey, toneKey }` — top-right status tag                                                                                                                                                                    |
| `statusIconMap`                        | —        | Maps status tone value → `pi` icon class                                                                                                                                                                          |
| `subtitle`                             | —        | `{ valueKey }` — muted line at the bottom of the card                                                                                                                                                             |
| `itemKey`                              | —        | Key field for `v-for`; default `'id'`                                                                                                                                                                             |
| `showDetails`                          | —        | Eye button per card → opens the detail dialog                                                                                                                                                                     |
| `canEdit` / `canArchive` / `canDelete` | —        | Default `false`. Render the matching gated inline action button (pencil / inbox / trash)                                                                                                                          |

## Events

- `@onEdit(item)` / `@onArchive(item)` / `@onDelete(item)` — emitted by the gated action buttons (inline **and** from the detail dialog) with the card's item.

## Slots

- `#details="{ item }"` — read-only detail content rendered inside the eyeball `MMainDialog`. Optional; the dialog is empty without it.
- `#actions="{ item, close }"` — custom per-card action buttons. Rendered **both** inline in the card **and** in the detail dialog footer, each with the correct `item`. `close()` dismisses the eyeball dialog — call it from an action triggered there.

## Eyeball dialog

The eye opens `MMainDialog` showing `#details`. **All actions — built-in `can*` buttons and the custom `#actions` slot — are mirrored into the dialog footer.** Built-in actions close the dialog before emitting; custom `#actions` run the parent handler and can call the slot's `close()` to dismiss it. With no actions, the dialog is `isFooterless` (read-only).

## Intentional Decisions

- **`amount` is generic, not a money hero** — optional, with `valueFormatter`/`align`. Financial usage (`{ valueKey }` only) is unchanged (back-compat).
- **Actions mirror inline ↔ dialog** — write a custom action once in `#actions`; it appears both inline and in the eyeball. Built-in `can*` buttons behave the same.
- **`tagClassByTone` is internal** — `error`/`warning` alias to `danger`/`warn`.

## Do Not

- **Do not add sorting or filtering** — display only; the parent prepares data (search `MultiSelect` + `MOrderBy` at the view level).
- **Do not hardcode tone/icon values** — drive via `statusIconMap` and tone keys from the parent.
