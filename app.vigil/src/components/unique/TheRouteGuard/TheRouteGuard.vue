<script setup lang="ts">
import { onBeforeMount, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useRoute } from 'vue-router';
import { GetMeService, useUserStore } from '@UserModule';
import useValidationCases from './useValidationCases';

const userStore = useUserStore();
const { isAuthenticated } = storeToRefs(userStore);

const ROUTE = useRoute();
const { NAVIGATION_MODES, navigationMode, targetRoute, isRedirecting, unauthenticatedRouteCase, publicRouteCase } =
  useValidationCases();

const canRender = ref(false);

onBeforeMount(onHardNavigation);

// HELPERS
function routeMiddleware(): boolean {
  return unauthenticatedRouteCase() && publicRouteCase();
}

function onSessionResolved() {
  targetRoute.value = ROUTE;

  const canProceed = routeMiddleware();

  if (canProceed) canRender.value = true;
}

// EVENTS
function onHardNavigation() {
  // Fully public routes (e.g. the status page) render for anyone — authenticated
  // or not — with no session probe and no redirect.
  if (ROUTE.meta?.allowAnonymous) {
    canRender.value = true;
    return;
  }

  if (isRedirecting.value) {
    if (isAuthenticated.value) onSessionResolved();
    return;
  }

  targetRoute.value = ROUTE;
  navigationMode.value = NAVIGATION_MODES.HARD;

  if (isAuthenticated.value) {
    onSessionResolved();
    return;
  }

  const isPublicRoute = !!ROUTE.meta?.isPublic;

  if (!userStore.hasSessionHint()) {
    if (isPublicRoute) {
      canRender.value = true;
      return;
    }

    onSessionResolved();
    return;
  }

  if (isPublicRoute) {
    canRender.value = true;

    GetMeService()
      .then(({ data }) => {
        if (data) {
          userStore.setUserAndPermissions(data, data.permissions ?? []);
          publicRouteCase();
        }
      })
      .catch(() => {});

    return;
  }

  GetMeService()
    .then(({ data }) => data && userStore.setUserAndPermissions(data, data.permissions ?? []))
    .catch(() => {})
    .finally(onSessionResolved);
}
</script>

<template>
  <slot v-if="canRender" />
</template>
