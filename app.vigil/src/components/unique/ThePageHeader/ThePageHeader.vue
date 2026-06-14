<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import useUiStore from '@Stores';

defineProps<{
  title: string;
}>();

const { t } = useI18n();
const uiStore = useUiStore();
const { isSidenavOpen } = storeToRefs(uiStore);
const { toggleSidenav } = uiStore;
</script>

<template>
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <Button
        plain
        text
        class="hidden md:flex lg:hidden"
        icon="pi pi-bars"
        :aria-expanded="isSidenavOpen"
        :aria-label="t('common.actions.menu')"
        @click="toggleSidenav()"
      />

      <h1
        data-page-title
        class="text-heading text-xl font-semibold tracking-tight"
      >
        {{ $t(title) }}
      </h1>
    </div>

    <slot name="actions" />
  </div>
</template>
