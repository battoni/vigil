<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router';

type NavbarRouteItem = {
  icon: string;
  label: string;
  route: RouteLocationRaw;
};

defineProps<{
  mobileItems: NavbarRouteItem[];
}>();

const emit = defineEmits<{
  'toggle-drawer': [];
}>();

// EVENTS
function onToggleDrawer() {
  emit('toggle-drawer');
}
</script>

<template>
  <div
    class="mobile-navbar-backdrop bg-canvas pointer-events-none fixed right-0 bottom-0 left-0 z-40 h-[calc(4.75rem+env(safe-area-inset-bottom,0))] md:hidden"
  />

  <div class="mobile-navbar-root fixed right-0 bottom-0 left-0 z-50 flex md:hidden">
    <div
      class="bg-panel mx-4 mb-4 flex w-full max-w-[100vw] min-w-0 items-center justify-center gap-1 rounded-t-xl px-2 py-3 shadow-lg"
    >
      <Button
        outlined
        plain
        class="shrink-0"
        icon="pi pi-bars"
        @click="onToggleDrawer"
      />

      <div class="flex min-w-0 flex-1 flex-nowrap items-center gap-1 overflow-x-auto px-2">
        <RouterLink
          v-for="{ icon, label, route } in mobileItems"
          class="hover:bg-panel-muted flex min-w-0 shrink-0 flex-row items-center justify-center gap-2 rounded-sm p-1 px-2 transition-colors duration-300 min-[480px]:gap-1.5 min-[480px]:p-1.5"
          exact-active-class="bg-primary-100 text-ink-950"
          :key="label"
          :to="route"
        >
          <span :class="['rounded-sm p-2 text-xl min-[480px]:p-1.5 min-[480px]:text-lg', icon]" />
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style>
body:has(.p-dialog-mask) .mobile-navbar-backdrop,
body:has(.p-dialog-mask) .mobile-navbar-root {
  display: none;
}
</style>
