<script setup lang="ts">
withDefaults(
  defineProps<{
    label?: string;
    wordmark?: string;
  }>(),
  {
    label: 'engineered by',
    wordmark: 'battoni.dev',
  }
);
</script>

<template>
  <div class="flex items-center gap-2.5">
    <ABrandMark class="text-ink-900 dark:text-primary-500 h-5 w-auto" />

    <div class="flex flex-col gap-0.5 leading-none">
      <span class="text-subtle font-mono text-[0.625rem] tracking-[0.18em] uppercase">{{ label }}</span>

      <span class="text-heading font-mono text-sm font-medium tracking-tight">{{ wordmark }}</span>
    </div>
  </div>
</template>
