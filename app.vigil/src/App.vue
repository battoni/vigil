<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useColorSchemeStore } from '@Stores';

const colorSchemeStore = useColorSchemeStore();

onMounted(onComponentMount);

// EVENTS
function onComponentMount() {
  colorSchemeStore.init();
}
</script>

<template>
  <Toast />

  <TheRouteGuard>
    <RouterView />
  </TheRouteGuard>
</template>
