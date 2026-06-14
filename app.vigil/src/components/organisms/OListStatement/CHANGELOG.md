# Changelog — OListStatement

All notable changes to this unit are documented here. Versioned with SemVer.
This file is the canonical source for the unit's version (RULES.md frontmatter mirrors it).

## 1.1.0 — 2026-06-05

### Added

- Generic optional `amount` (`valueFormatter`/`align`), read-only `#details="{ item }"` eyeball slot, inline gated actions (`canEdit`/`canArchive`/`canDelete` + `@onEdit`/`@onArchive`/`@onDelete`), and a custom `#actions="{ item }"` slot — all mirrored into the detail dialog footer.
- Unit tests (`OListStatement.spec.ts`) covering the new contract.

### Changed

- `amount` is now optional (was required). Existing financial usage is unchanged.

## 1.0.0 — 2026-05-30

### Added

- Initial documented baseline.
