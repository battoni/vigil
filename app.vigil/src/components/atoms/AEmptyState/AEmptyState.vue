<script setup lang="ts">
withDefaults(
  defineProps<{
    description?: string;
    glyph?: string;
    icon?: string;
    title?: string;
  }>(),
  {
    glyph: '>_',
  }
);
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-5 px-6 py-16 text-center">
    <div class="brand-corners border-line bg-panel flex size-16 items-center justify-center rounded-xl border">
      <i
        v-if="icon"
        :class="[icon, 'text-subtle text-2xl']"
      />

      <ABrandMark
        v-else
        class="text-ink-900 dark:text-primary-500 h-7 w-auto"
      />
    </div>

    <div class="flex flex-col items-center gap-1.5">
      <span class="text-subtle font-mono text-xs tracking-[0.18em] uppercase">{{ glyph }}</span>

      <h3
        v-if="title"
        class="text-heading text-lg font-semibold"
      >
        {{ title }}
      </h3>

      <p
        v-if="description"
        class="text-muted max-w-sm text-sm"
      >
        {{ description }}
      </p>
    </div>

    <div v-if="$slots.action">
      <slot name="action" />
    </div>
  </div>
</template>
