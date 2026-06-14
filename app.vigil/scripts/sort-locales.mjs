import { readFileSync, writeFileSync } from 'fs';
import { resolve } from 'path';

/**
 * Top-level keys grouped by category.
 * COMMON → COMPONENT → MODULE, alphabetical within each group.
 * Add new top-level keys here when creating new locale sections.
 */
const TOP_LEVEL_GROUPS = {
  common: 'common',
  errors: 'common',
  navigation: 'component',
  auth: 'module',
  finance: 'module',
  rolesAndPermissions: 'module',
  users: 'module',
};

function sortObj(obj) {
  const oneLiners = [];
  const multiLiners = [];

  for (const [key, value] of Object.entries(obj)) {
    if (typeof value === 'string') oneLiners.push(key);
    else multiLiners.push(key);
  }

  oneLiners.sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
  multiLiners.sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));

  const result = {};
  for (const key of [...oneLiners, ...multiLiners]) {
    result[key] = typeof obj[key] === 'object' ? sortObj(obj[key]) : obj[key];
  }
  return result;
}

function sortTopLevel(obj) {
  const groups = { common: [], component: [], module: [] };

  for (const key of Object.keys(obj)) {
    const group = TOP_LEVEL_GROUPS[key] ?? 'module';
    groups[group].push(key);
  }

  for (const group of Object.values(groups)) {
    group.sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
  }

  const result = {};
  for (const key of [...groups.common, ...groups.component, ...groups.module]) {
    result[key] = sortObj(obj[key]);
  }
  return result;
}

const localesDir = resolve(process.cwd(), 'src/locales');
const files = ['en.json', 'pt-BR.json'];

for (const file of files) {
  const filePath = resolve(localesDir, file);
  const raw = JSON.parse(readFileSync(filePath, 'utf8'));
  const sorted = sortTopLevel(raw);
  writeFileSync(filePath, JSON.stringify(sorted, null, 2) + '\n');
  console.log(`✓ ${file}`);
}
