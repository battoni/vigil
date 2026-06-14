# Changelog — Theme System

All notable changes to this unit are documented here. Versioned with SemVer.
This file is the canonical source for the unit's version (RULES.md frontmatter mirrors it).

## 3.0.0 — 2026-06-13

### Changed

- Replace the flat theme with the atmospheric battoni-dev identity (light+dark), env-driven active-theme selection, and scheme-flipping role tokens.

## 2.0.0 — 2026-06-10

### Added

- **Multi-theme architecture.** `colors.css` is now a thin active-theme selector that
  `@import`s one file from `themes/`. Added `themes/battoni-dev.css` (default) and
  `themes/_template.css` for new themes.
- **Light/dark mode.** Role tokens (`canvas`, `panel`, `panel-muted`, `heading`, `body`,
  `muted`, `subtle`, `line`, `line-strong`) flip under `[data-theme='dark']`. Runtime
  toggle via `colorScheme.store.ts` (persisted, system default) + anti-FOUC inline script.
- PrimeVue `semantic.colorScheme.dark` and per-component dark blocks; `darkModeSelector`
  set to `[data-theme="dark"]`.

### Changed

- **BREAKING:** components must use **role tokens** for structure instead of raw
  `surface-*` / `ink-*` shades. Migration map documented in RULES.md.

## 1.0.0 — 2026-05-30

### Added

- Initial documented baseline.
