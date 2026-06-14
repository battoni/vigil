<script setup lang="ts">
import useUiStore from '@Stores';

defineProps<{
  classes?: string;
}>();

useUiStore();
</script>

<template>
  <div class="flex h-full min-h-0 w-full justify-center">
    <div class="flex w-full sm:max-w-[640px] md:max-w-[768px] lg:max-w-[1024px] xl:max-w-[1280px] 2xl:max-w-[1536px]">
      <TheNavbar />

      <div class="mx-auto flex max-h-[calc(120vh-10rem)] min-h-0 w-full max-w-[1200px] flex-1 flex-col">
        <div
          v-if="$slots['pageHeader']"
          class="px-8 pt-8 pb-2"
        >
          <slot name="pageHeader" />
        </div>

        <main :class="['relative flex min-h-0 w-full flex-1 transform-[translateZ(0)] flex-col', classes]">
          <div
            class="main-scroll flex flex-1 flex-col gap-8 overflow-y-auto px-8 pt-8 pb-[calc(5.75rem+env(safe-area-inset-bottom,0))] md:pb-8"
          >
            <slot />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
