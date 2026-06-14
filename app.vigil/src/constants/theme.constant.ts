import { COLOR_SCHEME } from '@Enums';

/**
 * Active brand theme — single source of truth is the VITE_ACTIVE_THEME env var.
 * The CSS import (src/styles/theme/colors.css) resolves the same var through the
 * `@ActiveTheme` Vite alias, so both stay in sync from one place. Used here for
 * non-CSS concerns (e.g. picking brand assets).
 */
const ACTIVE_THEME = import.meta.env.VITE_ACTIVE_THEME || 'battoni-dev';

/** Default color scheme when the user has no stored preference. */
const DEFAULT_COLOR_SCHEME = COLOR_SCHEME.SYSTEM;

/**
 * localStorage key for the persisted color-scheme preference.
 * NOTE: kept in sync with the anti-FOUC inline script in index.html.
 */
const COLOR_SCHEME_STORAGE_KEY = 'app.vigil:color-scheme';

export { ACTIVE_THEME, COLOR_SCHEME_STORAGE_KEY, DEFAULT_COLOR_SCHEME };
