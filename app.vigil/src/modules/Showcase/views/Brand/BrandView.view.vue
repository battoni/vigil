<script setup lang="ts">
type ColorStep = { class: string; shade: string };
type ColorRamp = { main: string; name: string; note: string; steps: ColorStep[] };
type SemanticToken = { class: string; hex: string; name: string; use: string };

const codeGlyphs = ['>_', '</>', '{ }', '[ ]', '=>', '//', '« »', '*'];

const progressBars = [
  { label: 'tokens', value: 92 },
  { label: 'identity', value: 68 },
  { label: 'atmosphere', value: 41 },
];

const timelinePhases = [
  { num: '01', title: 'Foundation', detail: 'Ramps, role tokens, light/dark.' },
  { num: '02', title: 'Identity', detail: 'Geometry, mono voice, motifs.' },
  { num: '03', title: 'Atmosphere', detail: 'Glows, gradients, depth.' },
];

const semanticTokens: SemanticToken[] = [
  { name: 'success', class: 'bg-success-500', hex: '#22c55e', use: 'done · active · positive' },
  { name: 'info', class: 'bg-info-500', hex: '#3b82f6', use: 'neutral · informational' },
  { name: 'warn', class: 'bg-warn-500', hex: '#f59e0b', use: 'scheduled · pending' },
  { name: 'danger', class: 'bg-danger-500', hex: '#ef4444', use: 'error · destructive' },
  { name: 'help', class: 'bg-help-500', hex: '#a855f7', use: 'hints · support' },
  { name: 'contrast', class: 'bg-contrast-500', hex: '#737373', use: 'secondary · tertiary' },
];

const colorRamps: ColorRamp[] = [
  {
    name: 'primary',
    main: '#c0e021',
    note: 'brand action · the signature green',
    steps: [
      { shade: '50', class: 'bg-primary-50' },
      { shade: '100', class: 'bg-primary-100' },
      { shade: '200', class: 'bg-primary-200' },
      { shade: '300', class: 'bg-primary-300' },
      { shade: '400', class: 'bg-primary-400' },
      { shade: '500', class: 'bg-primary-500' },
      { shade: '600', class: 'bg-primary-600' },
      { shade: '700', class: 'bg-primary-700' },
      { shade: '800', class: 'bg-primary-800' },
      { shade: '900', class: 'bg-primary-900' },
      { shade: '950', class: 'bg-primary-950' },
    ],
  },
  {
    name: 'ink',
    main: '#141413',
    note: 'headings · strong text · the black',
    steps: [
      { shade: '50', class: 'bg-ink-50' },
      { shade: '100', class: 'bg-ink-100' },
      { shade: '200', class: 'bg-ink-200' },
      { shade: '300', class: 'bg-ink-300' },
      { shade: '400', class: 'bg-ink-400' },
      { shade: '500', class: 'bg-ink-500' },
      { shade: '600', class: 'bg-ink-600' },
      { shade: '700', class: 'bg-ink-700' },
      { shade: '800', class: 'bg-ink-800' },
      { shade: '900', class: 'bg-ink-900' },
      { shade: '950', class: 'bg-ink-950' },
    ],
  },
  {
    name: 'surface',
    main: '#888b8d',
    note: 'backgrounds · borders · muted · the gray',
    steps: [
      { shade: '50', class: 'bg-surface-50' },
      { shade: '100', class: 'bg-surface-100' },
      { shade: '200', class: 'bg-surface-200' },
      { shade: '300', class: 'bg-surface-300' },
      { shade: '400', class: 'bg-surface-400' },
      { shade: '500', class: 'bg-surface-500' },
      { shade: '600', class: 'bg-surface-600' },
      { shade: '700', class: 'bg-surface-700' },
      { shade: '800', class: 'bg-surface-800' },
      { shade: '900', class: 'bg-surface-900' },
      { shade: '950', class: 'bg-surface-950' },
    ],
  },
];
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="Brand" />
    </template>

    <div class="flex flex-col gap-14 pb-10">
      <!-- HERO -->
      <section class="border-line bg-primary-500 relative overflow-hidden rounded-xl border">
        <div class="text-ink-900 absolute inset-0 opacity-[0.06]">
          <ABrandPattern
            :rotate="-8"
            :tile="150"
          />
        </div>

        <div class="relative flex flex-col gap-8 p-8 sm:p-12">
          <ABrandMark class="text-ink-900 h-16 w-auto" />

          <div class="flex flex-col gap-4">
            <span class="text-ink-900/70 font-mono text-sm tracking-[0.2em] uppercase">{{
              '>_ app.vigil × battoni.dev'
            }}</span>

            <h2 class="text-ink-950 max-w-2xl text-4xl leading-[1.05] font-bold tracking-tight sm:text-5xl">
              A system built to move fast.
            </h2>

            <p class="text-ink-900/80 max-w-xl font-mono text-sm leading-relaxed">
              The Guilherme Battoni identity — retro-futuristic, technical, unmistakably green. This page is the living
              style guide for the battoni-dev theme.
            </p>
          </div>

          <div class="text-ink-900/70 flex flex-wrap gap-3 font-mono text-base">
            <span
              v-for="glyph in codeGlyphs"
              :key="glyph"
            >
              {{ glyph }}
            </span>
          </div>
        </div>
      </section>

      <!-- COLOR -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 01 · color</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">Color system</h2>
        </div>

        <div class="flex flex-col gap-6">
          <div
            v-for="ramp in colorRamps"
            class="flex flex-col gap-2"
            :key="ramp.name"
          >
            <div class="flex items-baseline justify-between">
              <span class="text-heading font-mono text-sm font-medium">{{ ramp.name }}</span>

              <span class="text-subtle font-mono text-xs">{{ ramp.note }} · {{ ramp.main }}</span>
            </div>

            <div class="flex flex-col gap-1.5">
              <div class="border-line grid grid-cols-11 overflow-hidden rounded-lg border">
                <div
                  v-for="step in ramp.steps"
                  class="h-12"
                  :key="step.shade"
                  :class="step.class"
                />
              </div>

              <div class="grid grid-cols-11">
                <span
                  v-for="step in ramp.steps"
                  class="text-subtle text-center font-mono text-[0.625rem]"
                  :key="step.shade"
                >
                  {{ step.shade }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <div
            v-for="token in semanticTokens"
            class="border-line bg-panel flex flex-col gap-2 rounded-lg border p-3"
            :key="token.name"
          >
            <div
              class="h-9 w-full rounded-md"
              :class="token.class"
            />

            <div class="flex flex-col gap-0.5">
              <div class="flex items-baseline justify-between gap-1">
                <span class="text-heading font-mono text-xs font-medium">{{ token.name }}</span>

                <span class="text-subtle font-mono text-[0.625rem]">{{ token.hex }}</span>
              </div>

              <span class="text-subtle text-[0.625rem]">{{ token.use }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- TYPOGRAPHY -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 02 · type</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">Typography</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <div class="border-line bg-panel flex flex-col gap-4 rounded-xl border p-6">
            <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">Space Grotesk · display</span>

            <p class="text-heading text-5xl font-bold tracking-tight">Build fast.</p>

            <p class="text-heading text-2xl font-semibold tracking-tight">Ship clean code.</p>

            <p class="text-body text-base">Headings, labels, navigation and UI chrome.</p>
          </div>

          <div class="border-line bg-panel flex flex-col gap-4 rounded-xl border p-6">
            <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">Sometype Mono · text</span>

            <p class="text-heading font-mono text-2xl font-medium">{ battoni.dev }</p>

            <p class="text-body font-mono text-sm leading-relaxed">
              Body copy, code blocks and tabular data. The monospaced voice keeps the communication dynamic and
              technical — true to the brand.
            </p>

            <p class="text-muted font-mono text-xs tracking-[0.18em] uppercase">0123456789 · &lt;/&gt; · =&gt;</p>
          </div>
        </div>
      </section>

      <!-- THE MARK -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 03 · the mark</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">The monogram</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <div class="bg-primary-500 flex aspect-[4/3] items-center justify-center rounded-xl">
            <ABrandMark class="text-ink-900 h-20 w-auto" />
          </div>

          <div class="bg-ink-900 flex aspect-[4/3] items-center justify-center rounded-xl">
            <ABrandMark class="text-primary-500 h-20 w-auto" />
          </div>

          <div class="border-line bg-panel flex aspect-[4/3] items-center justify-center rounded-xl border">
            <ABrandMark class="text-ink-900 dark:text-surface-50 h-20 w-auto" />
          </div>
        </div>

        <p class="text-muted max-w-2xl font-mono text-sm leading-relaxed">
          The "G" counterform carries a "B" — name, surname and craft fused into one programming-flavoured symbol. On
          green it reads black; on dark it glows green. Never deform, recolour or crowd it.
        </p>
      </section>

      <!-- VISUAL LANGUAGE -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 04 · visual language</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">Code as ornament</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
          <div class="grid grid-cols-4 gap-3">
            <div
              v-for="glyph in codeGlyphs"
              class="border-line bg-panel flex aspect-square items-center justify-center rounded-lg border"
              :key="glyph"
            >
              <span class="text-heading font-mono text-xl">{{ glyph }}</span>
            </div>
          </div>

          <div class="border-line bg-ink-900 relative overflow-hidden rounded-xl border">
            <div class="text-primary-500 absolute inset-0 opacity-10">
              <ABrandPattern
                :rotate="-8"
                :tile="120"
              />
            </div>

            <div class="relative flex h-full flex-col justify-end gap-1 p-5">
              <span class="text-primary-500 font-mono text-xs tracking-[0.2em] uppercase">pattern</span>

              <span class="text-surface-50 font-mono text-sm">monogram · low opacity</span>
            </div>
          </div>
        </div>
      </section>

      <!-- COMPONENTS -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 05 · components</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">In context</h2>
        </div>

        <div class="border-line bg-panel flex flex-col gap-6 rounded-xl border p-6">
          <div class="flex flex-wrap items-center gap-3">
            <Button
              label="Primary"
              severity="primary"
            />

            <Button
              label="Secondary"
              severity="secondary"
            />

            <Button
              label="Success"
              severity="success"
            />

            <Button
              label="Danger"
              severity="danger"
            />

            <Button
              icon="pi pi-bolt"
              label="With icon"
              severity="primary"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Tag value="Primary" />

            <Tag
              severity="success"
              value="Done"
            />

            <Tag
              severity="info"
              value="Scheduled"
            />

            <Tag
              severity="warn"
              value="Pending"
            />

            <Tag
              severity="danger"
              value="Canceled"
            />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
              <label class="text-muted block text-sm">Username</label>

              <InputText placeholder="Enter your username" />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-muted block text-sm">Role</label>

              <InputText placeholder="Select role" />
            </div>
          </div>
        </div>
      </section>

      <!-- SIGNATURE — living elements (these come alive under theme-2) -->
      <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
          <p class="text-subtle font-mono text-xs tracking-[0.2em] uppercase">// 06 · signature</p>

          <h2 class="text-heading text-2xl font-bold tracking-tight">Living elements</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <div class="border-line bg-panel flex flex-col gap-4 rounded-xl border p-6">
            <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">progress</span>

            <div
              v-for="bar in progressBars"
              class="flex flex-col gap-1.5"
              :key="bar.label"
            >
              <div class="flex items-baseline justify-between">
                <span class="text-muted font-mono text-xs uppercase">{{ bar.label }}</span>

                <span class="text-heading font-mono text-sm">{{ bar.value }}%</span>
              </div>

              <div class="bg-panel-muted h-2.5 overflow-hidden rounded-full">
                <div
                  class="brand-track bg-primary-500 h-full rounded-full"
                  :style="{ width: `${bar.value}%` }"
                />
              </div>
            </div>
          </div>

          <div class="border-line bg-panel flex flex-col gap-4 rounded-xl border p-6">
            <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">timeline</span>

            <div class="relative flex flex-col gap-5">
              <span class="bg-line absolute top-2 bottom-2 left-[5px] w-px" />

              <div
                v-for="phase in timelinePhases"
                class="relative pl-6"
                :key="phase.num"
              >
                <span class="brand-node bg-primary-500 absolute top-1 left-0 size-3 rounded-sm" />

                <h3 class="text-heading text-sm font-semibold">
                  <span class="text-primary-500 font-mono text-xs">{{ phase.num }}</span> {{ phase.title }}
                </h3>

                <p class="text-muted mt-0.5 text-sm">{{ phase.detail }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="border-line bg-panel flex flex-col gap-3 rounded-xl border p-6">
          <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">glossary</span>

          <p class="text-body text-sm leading-relaxed">
            Surface a <span class="brand-term">monogram</span> with a dotted lime underline — the reader gets the
            definition inline, no context lost.
          </p>

          <div class="brand-gloss">
            <b>monogram</b> — the "G" counterform that carries a "B"; the brand's core programming-flavoured mark.
          </div>
        </div>
      </section>
    </div>
  </TheLayout>
</template>
