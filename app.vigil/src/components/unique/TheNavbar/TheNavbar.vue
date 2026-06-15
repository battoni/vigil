<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { getI18nRouteName } from '@Helpers';
import useUiStore from '@Stores';
import { LogoutService, ROLES } from '@AuthModule';
import { useUserStore } from '@UserModule';

type NavItem = {
  icon: string;
  label: string;
  permission?: string;
  role?: string;
  routeName: string;
};

const uiStore = useUiStore();
const userStore = useUserStore();

const { t } = useI18n();
const { push, resolve } = useRouter();
const toast = useToast();

const isMobileView = ref(false);
const items = ref<NavItem[]>([
  { icon: 'pi pi-home', label: 'navigation.home', routeName: 'home' },
  { icon: 'pi pi-desktop', label: 'navigation.monitors', routeName: 'monitors' },
  { icon: 'pi pi-exclamation-triangle', label: 'navigation.incidents', routeName: 'incidents' },
  { icon: 'pi pi-bell', label: 'navigation.channels', routeName: 'channels' },
  { icon: 'pi pi-megaphone', label: 'navigation.statusPages', routeName: 'status-pages' },
  { icon: 'pi pi-wallet', label: 'navigation.finances', routeName: 'finance' },
]);
const settings = ref<NavItem[]>([
  { icon: 'pi pi-users', label: 'navigation.users', routeName: 'users' },
  { icon: 'pi pi-key', label: 'navigation.roles', routeName: 'roles-and-permissions' },
  { icon: 'pi pi-bolt', label: 'Brand', role: ROLES.SUPERADMIN, routeName: 'brand' },
  { icon: 'pi pi-palette', label: 'Components', role: ROLES.SUPERADMIN, routeName: 'components' },
  { icon: 'pi pi-star', label: 'Showcase', role: ROLES.SUPERADMIN, routeName: 'showcase' },
]);

const drawerClass = computed(() => (isMobileView.value ? 'nav-drawer-mobile' : 'w-54'));
const drawerPosition = computed(() => (isMobileView.value ? 'full' : 'left'));

const itemsWithRoutes = computed(() => {
  return items.value
    .filter((item) => !item.permission || userStore.hasPermission(item.permission))
    .map((item) => ({
      ...item,
      route: item.routeName === 'home' ? { name: 'home' } : resolve({ name: getI18nRouteName(item.routeName) }).path,
    }));
});

const mobileItems = computed(() => {
  const routes = ['finance', 'home', 'monitors', 'incidents', 'channels', 'status-pages', 'users', 'roles-and-permissions'];

  return itemsWithRoutes.value.filter((item) => routes.includes(item.routeName));
});

const settingsWithRoutes = computed(() => {
  return settings.value
    .filter((setting) => !setting.permission || userStore.hasPermission(setting.permission))
    .filter((setting) => !setting.role || userStore.user?.role_slug === setting.role)
    .map((setting) => ({
      ...setting,
      route: resolve({ name: getI18nRouteName(setting.routeName) }).path,
    }));
});

onMounted(onComponentMount);
onBeforeUnmount(onComponentBeforeUnmount);

// HELPERS
function closeTabletDrawer() {
  uiStore.closeSidenav();
}

function syncMobileState() {
  isMobileView.value = window.innerWidth < 768;
}

// EVENTS
function onComponentBeforeUnmount() {
  window.removeEventListener('resize', syncMobileState);
}

function onComponentMount() {
  syncMobileState();
  window.addEventListener('resize', syncMobileState);
}

function onLogout() {
  LogoutService()
    .then(() => {
      push('/login');

      userStore.clearUser();

      toast.add({
        severity: 'success',
        summary: t('auth.seeYouSoon'),
        life: 5000,
      });
    })
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        life: 5000,
      })
    );
}

function onToggleDrawer() {
  uiStore.toggleSidenav();
}
</script>

<template>
  <div class="w-0 shrink-0 overflow-visible lg:w-auto lg:shrink-0">
    <ONavbarDesktop
      :itemsWithRoutes
      :settingsWithRoutes
      @logout="onLogout"
    />

    <ONavbarDrawer
      :drawerClass
      :drawerPosition
      :itemsWithRoutes
      :settingsWithRoutes
      :visible="uiStore.isSidenavOpen"
      @close="closeTabletDrawer"
      @logout="onLogout"
      @update:visible="uiStore.isSidenavOpen = $event"
    />

    <ONavbarMobile
      :mobileItems
      @toggle-drawer="onToggleDrawer"
    />
  </div>
</template>

<style scoped>
@reference "../../../styles/main.css";

.router-link-exact-active {
  @apply font-semibold;
}

.router-link-exact-active .pi {
  @apply bg-primary-100 text-ink-950 font-semibold;
}

::deep(.nav-drawer-mobile) {
  height: 100vh;
  max-height: 100vh;
}
</style>
