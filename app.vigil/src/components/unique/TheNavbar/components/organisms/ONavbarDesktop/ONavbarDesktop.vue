<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { RouteLocationRaw } from 'vue-router';

type NavbarRouteItem = {
  icon: string;
  label: string;
  route: RouteLocationRaw;
};

const emit = defineEmits<{
  logout: [];
}>();

defineProps<{
  itemsWithRoutes: NavbarRouteItem[];
  settingsWithRoutes: NavbarRouteItem[];
}>();

const { t } = useI18n();

// EVENTS
function onLogoutClick() {
  emit('logout');
}
</script>

<template>
  <div class="hidden w-54 shrink-0 self-start py-8 lg:sticky lg:top-8 lg:ml-8 lg:block">
    <Card
      class="the-sidenav flex h-[calc(100dvh-4rem)] w-full flex-col shadow-none"
      pt:body="flex flex-col flex-1 h-full p-0"
      pt:content="flex flex-col flex-1"
    >
      <template #content>
        <div class="m-auto flex w-full max-w-[100px] shrink-0 items-center justify-center px-4 py-4">
          <ALogo variant="min" />
        </div>

        <Divider
          class="before:border-line mx-auto mt-0 mb-1 w-4/5"
          type="dashed"
        />

        <div class="flex w-full flex-1 flex-col items-start justify-start gap-1 overflow-y-auto p-2">
          <RouterLink
            v-for="{ icon, label, route } in itemsWithRoutes"
            v-slot="{ href, isExactActive, navigate }"
            custom
            :key="label"
            :to="route"
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
        </div>
      </template>

      <template #footer>
        <div class="sticky bottom-0 w-full shrink-0">
          <Divider
            class="before:border-line mx-auto mt-0 mb-1 w-4/5"
            type="dashed"
          />

          <div class="flex flex-col gap-1 p-2">
            <RouterLink
              v-for="{ icon, label, route } in settingsWithRoutes"
              v-slot="{ href, isExactActive, navigate }"
              custom
              :key="label"
              :to="route"
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
              @click="onLogoutClick"
            >
              <span class="pi pi-sign-out rounded-sm p-2 text-xl" />

              <span>{{ t('navigation.logout') }}</span>
            </Button>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
