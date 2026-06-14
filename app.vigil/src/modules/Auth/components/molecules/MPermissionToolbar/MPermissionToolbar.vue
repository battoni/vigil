<script setup lang="ts">
const emit = defineEmits<{
  onDeleteProfileRequest: [event: Event];
  onEditProfileRequest: [];
  onSavePermissionsRequest: [];
}>();

const props = withDefaults(
  defineProps<{
    loading: boolean;
    tab: string;
    canDeleteProfile?: boolean;
  }>(),
  { canDeleteProfile: true }
);

const modelValue = defineModel<boolean>({ required: true });
</script>

<template>
  <div class="mt-4 mb-4 flex min-w-0 flex-row gap-3 sm:items-center sm:justify-between">
    <div class="flex min-w-0 shrink-0 items-center gap-1.5 sm:gap-2">
      <label
        class="cursor-pointer truncate text-xs font-medium sm:text-sm"
        :for="`toggle-all-panels-${props.tab}`"
      >
        {{ modelValue ? $t('rolesAndPermissions.collapseAll') : $t('rolesAndPermissions.expandAll') }}
      </label>

      <ToggleSwitch
        v-model="modelValue"
        :id="`toggle-all-panels-${props.tab}`"
        :ariaLabel="modelValue ? $t('rolesAndPermissions.collapseAll') : $t('rolesAndPermissions.expandAll')"
        :disabled="loading"
      />
    </div>

    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
      <Button
        v-tooltip.top="$t('common.save')"
        iconOnly
        data-testid="save-permissions"
        icon="pi pi-save"
        :loading
        @click="emit('onSavePermissionsRequest')"
      />

      <Button
        v-tooltip.top="$t('common.actions.edit')"
        iconOnly
        icon="pi pi-pencil"
        severity="secondary"
        :loading
        @click="emit('onEditProfileRequest')"
      />

      <Button
        v-tooltip.top="
          canDeleteProfile ? $t('common.actions.delete') : $t('rolesAndPermissions.cannotDeleteAdministratorRole')
        "
        iconOnly
        icon="pi pi-trash"
        severity="danger"
        :disabled="!canDeleteProfile"
        :loading
        @click="(event) => emit('onDeleteProfileRequest', event)"
      />
    </div>
  </div>
</template>
