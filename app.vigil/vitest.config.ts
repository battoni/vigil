import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite';
import vue from '@vitejs/plugin-vue';
import { resolve, dirname } from 'node:path';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

// Aliases mirror vite.config.ts. Kept standalone (not importing vite.config) so unit
// tests don't pull in devtools/tailwind/auto-import plugins under jsdom. If you add a
// module alias in vite.config.ts + tsconfig.app.json, mirror it here too.
// Router stub must be listed FIRST so it takes precedence over the general @/ alias.
// This prevents routes.ts from spreading uninitialized module barrel exports in tests.
const routerStub = fileURLToPath(new URL('./src/test/mocks/router.stub.ts', import.meta.url));
// Exact-match alias for the @Libraries barrel prevents libraries/index.ts from loading
// './router' (relative, bypasses alias) which triggers the routes.ts circular-dep error.
const librariesBarrelStub = fileURLToPath(new URL('./src/test/mocks/libraries-barrel.stub.ts', import.meta.url));

const aliasEntries = [
  // Specific stubs — must appear before the generic @/ and @Libraries prefixes.
  { find: /^@\/libraries\/router(\/|$)/, replacement: routerStub },
  { find: /^@Libraries\/router(\/|$)/, replacement: routerStub },
  { find: /^@Libraries$/, replacement: librariesBarrelStub },
  { find: '@', replacement: fileURLToPath(new URL('./src', import.meta.url)) },
  { find: '@App', replacement: fileURLToPath(new URL('./src/App.vue', import.meta.url)) },
];

const moduleAliases = [
  'Assets',
  'Components',
  'Composables',
  'Constants',
  'Enums',
  'Helpers',
  'Interfaces',
  'Layouts',
  'Libraries',
  'Modules',
  'Plugins',
  'Providers',
  'Router',
  'Stores',
  'Styles',
  'Types',
];

moduleAliases.forEach((moduleName) => {
  aliasEntries.push({
    find: `@${moduleName}`,
    replacement: fileURLToPath(new URL(`./src/${moduleName.toLowerCase()}`, import.meta.url)),
  });
});

const moduleSubAliases = [
  { alias: 'AuthModule', path: './src/modules/Auth' },
  { alias: 'HomeModule', path: './src/modules/Home' },
  { alias: 'ShowcaseModule', path: './src/modules/Showcase' },
  { alias: 'UserModule', path: './src/modules/User' },
  { alias: 'ProjectModule', path: './src/modules/Project' },
  { alias: 'MonitorModule', path: './src/modules/Monitor' },
  { alias: 'IncidentModule', path: './src/modules/Incident' },
  { alias: 'ChannelModule', path: './src/modules/Channel' },
  { alias: 'StatusPageModule', path: './src/modules/StatusPage' },
];

moduleSubAliases.forEach(({ alias, path }) => {
  aliasEntries.push({
    find: `@${alias}`,
    replacement: fileURLToPath(new URL(path, import.meta.url)),
  });
});

export default defineConfig({
  plugins: [
    vue(),
    VueI18nPlugin({
      include: resolve(dirname(fileURLToPath(import.meta.url)), './src/locales/**'),
    }),
  ],
  resolve: {
    alias: aliasEntries,
  },
  test: {
    // Co-located unit specs live beside their unit: src/**/<Unit>.spec.ts
    // Integration specs use the same pattern with the .integration.spec.ts suffix.
    include: ['src/**/*.spec.ts'],
    env: {
      // Gives axios an absolute base so MSW can intercept the requests.
      VITE_API_URL: 'http://localhost',
      // Pin the UI language so specs asserting rendered i18n text are deterministic.
      // Without this, locale comes from a gitignored .env locally (en) but falls back to
      // the 'pt-BR' default on CI — which made CI red while local passed. See env.constant.ts.
      VITE_DEFAULT_LANGUAGE: 'en',
    },
    exclude: ['node_modules', 'dist', 'e2e'],
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    css: false,
    coverage: {
      provider: 'v8',
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.spec.ts', 'src/test/**', 'src/**/index.ts', 'src/**/*.d.ts'],
    },
  },
});
