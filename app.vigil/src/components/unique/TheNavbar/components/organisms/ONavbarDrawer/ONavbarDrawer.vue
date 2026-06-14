<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { RouteLocationRaw } from 'vue-router';

type NavbarRouteItem = {
  icon: string;
  label: string;
  route: RouteLocationRaw;
};

const emit = defineEmits<{
  close: [];
  logout: [];
  'update:visible': [isVisible: boolean];
}>();

const props = defineProps<{
  drawerClass: string;
  drawerPosition: string;
  itemsWithRoutes: NavbarRouteItem[];
  settingsWithRoutes: NavbarRouteItem[];
  visible: boolean;
}>();

const { t } = useI18n();

// EVENTS
function onCloseAction(closeCallback: () => void) {
  closeCallback();
  emit('close');
}

function onDrawerVisibilityUpdate(isVisible: boolean) {
  emit('update:visible', isVisible);
}

function onLogoutClick(closeCallback: () => void) {
  closeCallback();
  emit('close');
  emit('logout');
}
</script>

<template>
  <Drawer
    :class="[props.drawerClass, 'lg:hidden']"
    :position="props.drawerPosition"
    :visible="props.visible"
    @update:visible="onDrawerVisibilityUpdate"
  >
    <template #container="{ closeCallback }">
      <div class="flex h-full max-h-screen flex-col">
        <div class="border-line flex shrink-0 items-center justify-between px-4 py-3">
          <div class="w-full max-w-[50px]">
            <ALogo variant="min" />
          </div>

          <Button
            plain
            text
            class="absolute top-4 right-4"
            icon="pi pi-times"
            @click="onCloseAction(closeCallback)"
          />
        </div>

        <div class="flex flex-1 flex-col gap-1 overflow-y-auto p-2">
          <RouterLink
            v-for="{ icon, label, route } in props.itemsWithRoutes"
            v-slot="{ href, isExactActive, navigate }"
            custom
            :key="label"
            :to="route"
            @click="onCloseAction(closeCallback)"
          >
            <a
              :class="[
                'hover:bg-panel flex w-full items-center gap-2 rounded-sm p-1 px-2 transition-colors duration-300',
                { 'bg-panel': isExactActive },
              ]"
              :href
              @click="navigate"
            >
              <span :class="['rounded-sm p-2 text-xl', icon, { 'bg-primary-100 text-ink-950': isExactActive }]" />

              <span>{{ t(label) }}</span>
            </a>
          </RouterLink>

          <Divider
            class="before:border-line mx-auto mt-0 mb-1 w-4/5"
            type="dashed"
          />
        </div>

        <div class="border-line shrink-0 p-2">
          <Divider
            class="before:border-line mx-auto mt-0 mb-1 w-4/5"
            type="dashed"
          />

          <RouterLink
            v-for="{ icon, label, route } in props.settingsWithRoutes"
            v-slot="{ href, isExactActive, navigate }"
            custom
            :key="label"
            :to="route"
            @click="onCloseAction(closeCallback)"
          >
            <a
              :class="[
                'hover:bg-panel flex w-full items-center gap-2 rounded-sm p-1 px-2 transition-colors duration-300',
                { 'bg-panel': isExactActive },
              ]"
              :href
              @click="navigate"
            >
              <span :class="['rounded-sm p-2 text-xl', icon, { 'bg-primary-100 text-ink-950': isExactActive }]" />

              <span>{{ t(label) }}</span>
            </a>
          </RouterLink>

          <AColorSchemeToggle />

          <Button
            plain
            text
            unstyled
            class="hover:bg-primary-100 hover:text-ink-950 flex w-full cursor-pointer items-center gap-2 rounded-sm p-1 px-2 transition-colors duration-300"
            type="button"
            @click="onLogoutClick(closeCallback)"
          >
            <span class="pi pi-sign-out rounded-sm p-2 text-xl" />

            <span>{{ t('navigation.logout') }}</span>
          </Button>
        </div>
      </div>
    </template>
  </Drawer>
</template>
