import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import jsoncPlugin from 'eslint-plugin-jsonc';
import perfectionist from 'eslint-plugin-perfectionist';
import pluginVue from 'eslint-plugin-vue';
import { globalIgnores } from 'eslint/config';

export default defineConfigWithVueTs(
  {
    name: 'app/files-to-lint',
    files: ['**/*.{ts,mts,tsx,vue}'],
  },

  globalIgnores([
    '**/dist/**',
    '**/dist-ssr/**',
    '**/coverage/**',
    '**/playwright-report/**',
    '**/test-results/**',
    '**/playwright/.cache/**',
  ]),

  pluginVue.configs['flat/essential'],
  vueTsConfigs.recommended,
  skipFormatting,

  {
    plugins: {
      perfectionist,
    },
    rules: {
      // ── TypeScript ────────────────────────────────────────────────────────────
      '@typescript-eslint/no-explicit-any': 'off',

      // Enforce `import type` syntax for type-only imports
      '@typescript-eslint/consistent-type-imports': [
        'error',
        { prefer: 'type-imports', fixStyle: 'separate-type-imports' },
      ],

      // Enums must be UPPER_CASE (not PascalCase, which looks like an interface)
      '@typescript-eslint/naming-convention': [
        'error',
        { selector: 'enum', format: ['UPPER_CASE'] },
      ],

      // ── Imports ───────────────────────────────────────────────────────────────
      'perfectionist/sort-imports': [
        'error',
        {
          type: 'alphabetical',
          order: 'asc',
          newlinesBetween: 0,
          groups: [
            'vue',
            'external',
            // All type imports grouped together between external and internal
            'all-types',
            'internal',
            'modules',
            ['parent', 'sibling', 'index'],
          ],
          customGroups: [
            { groupName: 'vue', elementNamePattern: '^vue$' },
            // all-types must come before path-based groups so it wins for type imports
            { groupName: 'all-types', selector: 'type' },
            { groupName: 'internal', elementNamePattern: '^@[A-Z](?!\\w+Module)' },
            { groupName: 'modules', elementNamePattern: '^@[A-Z]\\w+Module' },
          ],
        },
      ],

      // ── Logic ─────────────────────────────────────────────────────────────────
      // No `else` after a return — use early returns instead
      'no-else-return': 'error',

      // Catch blocks must not be empty
      'no-empty': ['error', { allowEmptyCatch: false }],

      // ── Vue template ──────────────────────────────────────────────────────────
      'vue/v-bind-style': ['error', 'shorthand', { sameNameShorthand: 'always' }],

      'vue/attributes-order': [
        'error',
        {
          order: [
            'LIST_RENDERING',           // v-for
            'CONDITIONALS',             // v-if, v-else-if, v-else, v-show
            'RENDER_MODIFIERS',         // v-pre, v-once
            'SLOT',                     // v-slot
            'TWO_WAY_BINDING',          // v-model, v-model:visible
            'OTHER_DIRECTIVES',         // other directives (v-tooltip, etc.)
            'ATTR_SHORTHAND_BOOL',      // fluid, plain, text (boolean, no value)
            'GLOBAL',                   // id only (before other static attrs)
            ['ATTR_STATIC', 'UNIQUE'],  // class, key, mask, name, pt:body="...", etc. — all ASC alphabetically
            'ATTR_DYNAMIC',             // :class, :id, :modelValue (bound, all ASC)
            'EVENTS',                   // @click, @submit
            'CONTENT',                  // v-html, v-text
          ],
          alphabetical: true,
        },
      ],
    },
  },

  // ── Locale JSON files ──────────────────────────────────────────────────────
  {
    name: 'app/locales',
    files: ['src/locales/**/*.json'],
    plugins: { jsonc: jsoncPlugin },
    languageOptions: { parser: jsoncPlugin },
    rules: {
      '@typescript-eslint/consistent-type-imports': 'off',
      '@typescript-eslint/naming-convention': 'off',
      'jsonc/no-dupe-keys': 'error',
      'jsonc/valid-json-number': 'error',
    },
  }
);
