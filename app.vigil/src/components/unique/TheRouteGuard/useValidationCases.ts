import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import type { RouteLocationNormalized } from 'vue-router';
import { useUserStore } from '@UserModule';
import { NAVIGATION_MODES } from './enums';

const navigationMode = ref<NAVIGATION_MODES>();
const targetRoute = ref<RouteLocationNormalized>();
const isRedirecting = ref(false);

export default function useValidationCases() {
  const { isAuthenticated } = storeToRefs(useUserStore());

  const ROUTER = useRouter();
  const { t } = useI18n();
  const toast = useToast();

  function toggleRedirectState() {
    isRedirecting.value = !isRedirecting.value;
  }

  function unauthenticatedRouteCase(): boolean {
    const isPublicRoute = !!targetRoute.value?.meta?.isPublic;
    const VALID_CASE = !isAuthenticated.value && !isPublicRoute;

    if (!VALID_CASE) return true;

    toggleRedirectState();
    ROUTER.push({ path: '/entrar' }).finally(() => toggleRedirectState());

    return false;
  }

  function publicRouteCase(): boolean {
    const isPublicRoute = !!targetRoute.value?.meta?.isPublic;
    const VALID_CASE = isAuthenticated.value && isPublicRoute;

    if (!VALID_CASE) return true;

    toggleRedirectState();

    if (navigationMode.value === NAVIGATION_MODES.SOFT) {
      toast.add({ severity: 'error', summary: t('common.error'), life: 5000 });
      toggleRedirectState();
      return false;
    }

    ROUTER.push({ name: 'home' }).finally(() => toggleRedirectState());

    return false;
  }

  return {
    NAVIGATION_MODES,
    isRedirecting,
    navigationMode,
    targetRoute,

    unauthenticatedRouteCase,
    publicRouteCase,
  };
}
