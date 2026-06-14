# Changelog — OTabView / OTabPanel

All notable changes to this unit are documented here. Versioned with SemVer.
This file is the canonical source for the unit's version (RULES.md frontmatter mirrors it).

## 1.1.0 — 2026-06-05

### Added

- Unit tests (`OTabView.spec.ts`) covering view mode (titles, icon, actions slot, default/active content, model updates) and routes mode (RouterView rendering, route navigation).

## 1.0.0 — 2026-06-05

### Added

- Initial `OTabView` + `OTabPanel` organism. Compound, descriptor-based tabs with `view` and `routes` modes, per-tab title/icon, `#actions` slot, and `v-model` via a writable `computed`.
