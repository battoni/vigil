<script setup lang="ts">
import { computed } from 'vue';
import type { PermissionGroup } from '../../../views/RolesAndPermissions/interfaces';

const props = defineProps<{
  allPanelsExpanded: boolean;
  loading: boolean;
  permissionGroup: PermissionGroup;
}>();

const collapsed = defineModel<boolean>({ default: true });

const allSelected = computed({
  get: () => props.permissionGroup.permissions.every((permission) => permission.value),
  set: (value: boolean) => {
    props.permissionGroup.permissions.forEach((permission) => {
      permission.value = value;
    });
  },
});
</script>

<template>
  <Panel
    v-model:collapsed="collapsed"
    toggleable
    class="bg-panel rounded-2xl border-0 p-4 shadow-none"
    pt:header="gap-2"
  >
    <template #header>
      <div class="flex w-full min-w-0 items-center gap-2 sm:gap-4">
        <i
          :class="[
            permissionGroup.icon,
            'bg-primary-100 text-ink-950 shrink-0 rounded-sm p-1.5 text-lg font-semibold sm:p-2 sm:text-xl',
          ]"
        />

        <h3 class="min-w-0 truncate text-base font-semibold sm:text-xl">{{ $t(permissionGroup.nameKey) }}</h3>
      </div>
    </template>

    <div class="flex flex-col gap-4 pt-4">
      <div
        v-for="permission in permissionGroup.permissions"
        class="flex items-center justify-start gap-4"
        :key="permission.key"
      >
        <ToggleSwitch
          v-model="permission.value"
          :id="permission.key"
          :ariaLabel="`${$t(permission.labelKey)} permission`"
          :disabled="loading"
        />

        <label
          class="cursor-pointer text-base font-medium"
          :for="permission.key"
        >
          {{ $t(permission.labelKey) }}
        </label>
      </div>

      <div class="flex w-full justify-end pt-2">
        <ToggleSwitch
          v-model="allSelected"
          v-tooltip.top="{
            content: $t(allSelected ? 'rolesAndPermissions.deactivateAll' : 'rolesAndPermissions.activateAll'),
          }"
          :id="`toggle-all-${permissionGroup.id}`"
          :ariaLabel="allSelected ? $t('rolesAndPermissions.deactivateAll') : $t('rolesAndPermissions.activateAll')"
          :disabled="loading"
        />
      </div>
    </div>
  </Panel>
</template>
