---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — AEmptyState

> For AI agents.

## Purpose

The branded empty-state placeholder for lists/grids with no data: a bracketed tile
(monogram or icon), a `>_` code-symbol glyph, optional title/description, and an action slot.

## Intentional Decisions

- **Monogram by default, icon on demand**: pass `icon` to swap the `ABrandMark` for a PrimeIcon.
- **`>_` glyph**: the brand's terminal cue; overridable via `glyph`.
- **`.brand-corners`**: corner-bracket overlay (theme-driven) frames the tile.
- **All text optional**: `title`/`description` only render when provided; copy is passed pre-translated by the caller (`$t(...)`).

## Prop & Emit Contract

- `title?`, `description?`: pre-translated strings.
- `glyph?`: terminal glyph (default `>_`).
- `icon?`: PrimeIcon class; when set, replaces the monogram.
- Slot `action`: optional CTA (e.g. an "Add" button).

## Do Not

- Do not import `ABrandMark` — it is auto-imported.
- Do not pass i18n keys; pass already-translated strings.
