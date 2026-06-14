// Global Vitest setup — runs once before each test file.
// Keep this minimal; per-test plugin wiring lives in src/test/mount.ts and render.ts.

import '@testing-library/jest-dom';
import { config } from '@vue/test-utils';
import { afterAll, afterEach, beforeAll } from 'vitest';
import { server } from './msw/server';

// Stub PrimeVue's `tooltip` directive (registered globally in main.ts) so components
// using v-tooltip mount cleanly in unit tests.
config.global.directives = {
  tooltip: () => {},
};

// MSW — start once, reset handlers after each test, tear down after the suite.
// Unit specs are unaffected: MSW only intercepts real network requests.
beforeAll(() => server.listen({ onUnhandledRequest: 'warn' }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
