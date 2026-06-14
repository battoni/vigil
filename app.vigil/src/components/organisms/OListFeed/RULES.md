---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---

# RULES — OListFeed

> For AI agents. Last updated: 2026-06-05.

## Purpose

A generic activity-feed organism: rows with a colored icon circle (driven by badge tone), a two-line center text block, and a right column with amount and status. Used for **read-only timelines** (e.g. version history) as well as managed lists. Works for any data shape — the parent maps data keys to visual roles via props.

## Props Contract

| Prop                                   | Required | Description                                                                                                                                                                                                               |
| -------------------------------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `items`                                | ✓        | Array of plain objects — any shape                                                                                                                                                                                        |
| `title`                                | ✓        | `{ valueKey }` — primary text (center, top)                                                                                                                                                                               |
| `amount`                               | —        | **Optional.** `{ valueKey, valueFormatter?, align? }`. When omitted (e.g. a pure timeline) the row renders cleanly. `valueFormatter(raw) => string` formats non-money values; `align: 'left' \| 'right'` (default right). |
| `badge`                                | —        | `{ valueKey, toneKey }` — tone drives the icon-circle color; value appears in the subtitle line                                                                                                                           |
| `badgeIconMap`                         | —        | Maps badge value → feed icon (`pi` class); default `pi pi-circle`                                                                                                                                                         |
| `status`                               | —        | `{ valueKey, toneKey }` — right status tag                                                                                                                                                                                |
| `statusIconMap`                        | —        | Maps status tone value → `pi` icon class                                                                                                                                                                                  |
| `subtitle`                             | —        | `{ valueKey }` — muted text after the badge value                                                                                                                                                                         |
| `itemKey`                              | —        | Key field for `v-for`; default `'id'`                                                                                                                                                                                     |
| `showDetails`                          | —        | Eye button per row → opens the detail dialog                                                                                                                                                                              |
| `canEdit` / `canArchive` / `canDelete` | —        | Default `false`. Render the matching gated inline action button (pencil / inbox / trash)                                                                                                                                  |

## Events

- `@onEdit(item)` / `@onArchive(item)` / `@onDelete(item)` — emitted by the gated action buttons (inline **and** from the detail dialog) with the row's item.

## Slots

- `#details="{ item }"` — read-only detail content rendered inside the eyeball `MMainDialog`. Optional; the dialog is empty without it.
- `#actions="{ item, close }"` — custom per-row action buttons. Rendered **both** inline in the row **and** in the detail dialog footer, each with the correct `item`. `close()` dismisses the eyeball dialog — call it from an action triggered there.

## Eyeball dialog

The eye opens `MMainDialog` showing `#details`. **All actions — built-in `can*` buttons and the custom `#actions` slot — are mirrored into the dialog footer.** Built-in actions close the dialog before emitting; custom `#actions` run the parent handler and can call the slot's `close()` to dismiss it. With no actions, the dialog is `isFooterless` (read-only).

## Intentional Decisions

- **`badge` tone drives the icon-circle color** (`iconBgClass`/`iconBgByTone`, internal); `badgeIconMap` picks the icon glyph.
- **`amount` is generic, not a money hero** — optional, with `valueFormatter`/`align`. Read-only timelines typically pass a formatted value or omit it. Financial usage (`{ valueKey }` only) is unchanged (back-compat).
- **Actions mirror inline ↔ dialog** — write a custom action once in `#actions`; it appears both inline and in the eyeball. Built-in `can*` buttons behave the same.

## Do Not

- **Do not add sorting or filtering** — display only; the parent prepares data (search `MultiSelect` + `MOrderBy` at the view level).
- **Do not hardcode tone/icon values** — drive via `badgeIconMap`/`statusIconMap` and tone keys from the parent.
