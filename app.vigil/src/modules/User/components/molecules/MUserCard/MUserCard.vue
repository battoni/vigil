<script setup lang="ts">
import { computed } from 'vue';
import type { User } from '../../../interfaces';

const emit = defineEmits<{
  onArchiveUserRequest: [event: Event];
  onDeleteUserRequest: [event: Event];
  onEditUserRequest: [];
}>();

const props = withDefaults(
  defineProps<{
    user: User;
    canEdit?: boolean;
    canArchive?: boolean;
    canDelete?: boolean;
  }>(),
  {
    canEdit: true,
    canArchive: true,
    canDelete: true,
  }
);

const fullName = computed(() => `${props.user.name} ${props.user.last_name}`);

const initials = computed(() => {
  const first = props.user.name?.charAt(0)?.toUpperCase() || '';
  const last = props.user.last_name?.charAt(0)?.toUpperCase() || '';

  return first + last;
});

const isInactive = computed(() => {
  const status = props.user.status?.toLowerCase();

  return status === 'inactive' || status === 'inativo';
});

// EVENTS
function onArchiveUserRequestClick(event: Event) {
  emit('onArchiveUserRequest', event);
}

function onDeleteUserRequestClick(event: Event) {
  emit('onDeleteUserRequest', event);
}

function onEditUserRequestClick() {
  emit('onEditUserRequest');
}
</script>

<template>
  <Card
    data-testid="user-card"
    pt:body="pb-0"
    pt:header="flex justify-between items-center px-4 pt-4"
    :class="['bg-panel rounded-md border-t-16 p-4', isInactive ? 'border-danger-600' : 'border-primary-600']"
  >
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <Avatar
          size="xlarge"
          :image="user.profile_picture"
          :label="initials"
        />

        <div class="flex flex-col gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <Tag
              class="celer-tag-bold-primary"
              :value="user.role"
            />

            <Tag
              v-if="isInactive"
              severity="danger"
              :value="$t('common.inactive')"
            />

            <Tag
              v-else
              :value="$t('common.active')"
            />
          </div>

          <h3 class="text-heading text-xl font-bold">{{ fullName }}</h3>
        </div>
      </div>
    </template>

    <template #footer>
      <Divider type="dashed" />

      <div class="flex w-full items-end justify-end gap-2">
        <Button
          v-if="canEdit"
          v-tooltip.top="$t('common.actions.edit')"
          plain
          text
          class="celer-button-text-info"
          icon="pi pi-pencil"
          @click="onEditUserRequestClick"
        />

        <Button
          v-if="canArchive"
          v-tooltip.top="isInactive ? $t('common.actions.activate') : $t('common.actions.archive')"
          plain
          text
          class="celer-button-text-warn"
          type="button"
          :icon="isInactive ? 'pi pi-folder-plus' : 'pi pi-folder-open'"
          @click="onArchiveUserRequestClick"
        />

        <Button
          v-if="isInactive && canDelete"
          v-tooltip.top="$t('common.actions.delete')"
          plain
          text
          class="celer-button-text-danger"
          icon="pi pi-trash"
          type="button"
          @click="onDeleteUserRequestClick"
        />
      </div>
    </template>
  </Card>
</template>
