// Generates VitePress pages from the canonical rule and command files in
// `.claude/`. Single source of truth: rules and commands live at the monorepo
// root and are mirrored here so the handbook never drifts. Edit the source,
// not the output page.
//
// Output:
//   codelumen/rules/<project>/<slug>.md                  one page per rule
//   codelumen/commands/<name>.md                         one page per command
//   codelumen/.vitepress/generated/rules-sidebar.json    rule sidebar data
//   codelumen/.vitepress/generated/commands-sidebar.json command sidebar data
//
// Deploy guard: if `.claude/rules` is not present (e.g. a Vercel build whose
// root excludes the parent dir), the script logs and exits 0, leaving the
// already-committed generated files untouched.

import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const CODELUMEN = resolve(HERE, '..');
const RULES_SRC = resolve(CODELUMEN, '..', '.claude', 'rules');
const RULES_OUT = join(CODELUMEN, 'rules');
const SIDEBAR_OUT = join(CODELUMEN, '.vitepress', 'generated', 'rules-sidebar.json');

const COMMANDS_SRC = resolve(CODELUMEN, '..', '.claude', 'commands');
const COMMANDS_OUT = join(CODELUMEN, 'commands');
const COMMANDS_SIDEBAR_OUT = join(CODELUMEN, '.vitepress', 'generated', 'commands-sidebar.json');

const PROJECT_LABELS = {
  api.vigil: 'Arcus',
  app.vigil: 'app.vigil',
  shared: 'Shared',
};

const ACRONYMS = { api: 'API', html: 'HTML', ts: 'TS', ui: 'UI', vue: 'Vue' };

// HELPERS

function parseFrontmatter(raw) {
  if (!raw.startsWith('---')) return { data: {}, body: raw };

  const end = raw.indexOf('\n---', 3);

  if (end === -1) return { data: {}, body: raw };

  const block = raw.slice(3, end).trim();
  const body = raw.slice(end + 4).replace(/^\n/, '');
  const data = {};

  for (const line of block.split('\n')) {
    const match = line.match(/^(\w+):\s*(.*)$/);

    if (!match) continue;

    const [, key, value] = match;

    data[key] = value.trim();
  }

  return { data, body };
}

function parseGlobs(value) {
  if (!value) return [];

  try {
    return JSON.parse(value);
  } catch {
    return [value.replace(/^["']|["']$/g, '')];
  }
}

function titleCase(slug) {
  return slug
    .replace(/^\d+-/, '')
    .split('-')
    .map((word) => ACRONYMS[word] ?? word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function splitName(filename) {
  const base = filename.replace(/\.md$/, '');
  const project = base.slice(0, base.indexOf('-'));
  const slug = base.slice(base.indexOf('-') + 1);

  return { project, slug };
}

// VitePress compiles markdown as a Vue template, so a bare `<word>` in prose
// (e.g. a `<project>` placeholder) is read as an unclosed tag and breaks the
// build. Escape `<` everywhere except inside fenced or inline code, where it is
// already literal. `>` is left alone so blockquotes keep working.
function escapeAngles(body) {
  let inFence = false;

  return body
    .split('\n')
    .map((line) => {
      if (/^\s*```/.test(line)) {
        inFence = !inFence;
        return line;
      }

      if (inFence) return line;

      return line.replace(/(`[^`]*`)|</g, (match, code) => (code ? code : '&lt;'));
    })
    .join('\n');
}

function activationLine(data) {
  const globs = parseGlobs(data.globs);

  if (data.alwaysApply === 'true' || globs.length === 0) return 'Always active across the monorepo.';

  return `Activates for: ${globs.map((glob) => `\`${glob}\``).join(', ')}.`;
}

function renderPage(filename, data, body) {
  const sourceRef = `.claude/rules/${filename}`;
  const title = data.description ?? titleCase(splitName(filename).slug);

  return `---
title: ${title}
outline: deep
---

::: info Generated page
This page is generated from \`${sourceRef}\` — **edit the source rule, not this page.** ${activationLine(data)}
:::

${escapeAngles(body).trimEnd()}
`;
}

function renderCommandPage(filename, body) {
  const name = filename.replace(/\.md$/, '');

  return `---
title: /${name}
outline: deep
---

::: info Generated page
This page is generated from \`.claude/commands/${filename}\` — run it with \`/${name}\`. **Edit the source command, not this page.**
:::

${escapeAngles(body).trimEnd()}
`;
}

function generateCommands() {
  if (!existsSync(COMMANDS_SRC)) return 0;

  rmSync(COMMANDS_OUT, { recursive: true, force: true });
  mkdirSync(COMMANDS_OUT, { recursive: true });

  const files = readdirSync(COMMANDS_SRC)
    .filter((name) => name.endsWith('.md'))
    .sort();

  const sidebar = [];

  for (const filename of files) {
    const raw = readFileSync(join(COMMANDS_SRC, filename), 'utf8');
    const { body } = parseFrontmatter(raw);
    const name = filename.replace(/\.md$/, '');

    writeFileSync(join(COMMANDS_OUT, filename), renderCommandPage(filename, body));
    sidebar.push({ text: `/${name}`, link: `/commands/${name}` });
  }

  mkdirSync(dirname(COMMANDS_SIDEBAR_OUT), { recursive: true });
  writeFileSync(COMMANDS_SIDEBAR_OUT, `${JSON.stringify(sidebar, null, 2)}\n`);

  return sidebar.length;
}

// MAIN

function main() {
  if (!existsSync(RULES_SRC)) {
    console.warn(`[generate-rules] ${RULES_SRC} not found — keeping committed output.`);
    process.exit(0);
  }

  rmSync(RULES_OUT, { recursive: true, force: true });

  const files = readdirSync(RULES_SRC)
    .filter((name) => name.endsWith('.md'))
    .sort();

  const sidebar = {};

  for (const filename of files) {
    const { project, slug } = splitName(filename);

    if (!PROJECT_LABELS[project]) {
      console.warn(`[generate-rules] skipping ${filename} — unknown project prefix.`);
      continue;
    }

    const raw = readFileSync(join(RULES_SRC, filename), 'utf8');
    const { data, body } = parseFrontmatter(raw);

    const outDir = join(RULES_OUT, project);

    mkdirSync(outDir, { recursive: true });
    writeFileSync(join(outDir, `${slug}.md`), renderPage(filename, data, body));

    sidebar[project] ??= [];
    sidebar[project].push({ text: titleCase(slug), link: `/rules/${project}/${slug}` });
  }

  mkdirSync(dirname(SIDEBAR_OUT), { recursive: true });
  writeFileSync(SIDEBAR_OUT, `${JSON.stringify(sidebar, null, 2)}\n`);

  const count = Object.values(sidebar).reduce((total, items) => total + items.length, 0);
  const commandCount = generateCommands();

  console.log(
    `[generate-rules] wrote ${count} rule pages across ${Object.keys(sidebar).length} projects, and ${commandCount} command pages.`,
  );
}

main();
