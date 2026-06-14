<script setup lang="ts">
import { computed, ref, useSlots, watch } from 'vue';

interface AmountFieldConfig {
  align?: 'left' | 'right';
  valueFormatter?: (raw: unknown) => string;
  valueKey: string;
}

interface TagFieldConfig {
  toneKey: string;
  valueKey: string;
}

interface TextFieldConfig {
  valueKey: string;
}

const emit = defineEmits<{
  onArchive: [item: Record<string, unknown>];
  onDelete: [item: Record<string, unknown>];
  onEdit: [item: Record<string, unknown>];
}>();

const props = withDefaults(
  defineProps<{
    amount?: AmountFieldConfig;
    badge?: TagFieldConfig;
    badgeIconMap?: Record<string, string>;
    canArchive?: boolean;
    canDelete?: boolean;
    canEdit?: boolean;
    itemKey?: string;
    items: Record<string, unknown>[];
    showDetails?: boolean;
    status?: TagFieldConfig;
    statusIconMap?: Record<string, string>;
    subtitle?: TextFieldConfig;
    title: TextFieldConfig;
  }>(),
  {
    canArchive: false,
    canDelete: false,
    canEdit: false,
    itemKey: 'id',
    showDetails: false,
  }
);

const slots = useSlots();

const iconBgByTone: Record<string, string> = {
  danger: 'bg-danger-100 text-danger-700',
  error: 'bg-danger-100 text-danger-700',
  info: 'bg-info-100 text-info-700',
  primary: 'bg-primary-100 text-primary-700',
  success: 'bg-success-100 text-success-700',
  warn: 'bg-warn-100 text-warn-700',
  warning: 'bg-warn-100 text-warn-700',
};

const tagClassByTone: Record<string, string> = {
  danger: 'celer-tag-soft-danger',
  error: 'celer-tag-soft-danger',
  info: 'celer-tag-soft-info',
  primary: 'celer-tag-soft-primary',
  success: 'celer-tag-soft-success',
  warn: 'celer-tag-soft-warn',
  warning: 'celer-tag-soft-warn',
};

const detailDialogVisible = ref(false);
const selectedItem = ref<Record<string, unknown> | null>(null);

const hasActions = computed(() => props.canEdit || props.canArchive || props.canDelete || !!slots.actions);

watch(detailDialogVisible, onDetailVisibleChange);

// HELPERS
function amountValue(item: Record<string, unknown>): string {
  if (!props.amount) return '';

  const raw = item[props.amount.valueKey];
  return props.amount.valueFormatter ? props.amount.valueFormatter(raw) : String(raw ?? '');
}

function closeDetail() {
  detailDialogVisible.value = false;
}

function feedIcon(item: Record<string, unknown>): string {
  if (!props.badge || !props.badgeIconMap) return 'pi pi-circle';

  return props.badgeIconMap[fieldValue(item, props.badge.valueKey)] ?? 'pi pi-circle';
}

function fieldValue(item: Record<string, unknown>, key: string): string {
  return String(item[key] ?? '');
}

function iconBgClass(item: Record<string, unknown>): string {
  if (!props.badge) return 'bg-panel-muted text-muted';

  return iconBgByTone[fieldValue(item, props.badge.toneKey)] ?? 'bg-panel-muted text-muted';
}

function tagClass(item: Record<string, unknown>, config: TagFieldConfig): string {
  return tagClassByTone[fieldValue(item, config.toneKey)] ?? '';
}

// EVENTS
function onArchiveClick(item: Record<string, unknown> | null) {
  if (!item) return;

  detailDialogVisible.value = false;
  emit('onArchive', item);
}

function onDeleteClick(item: Record<string, unknown> | null) {
  if (!item) return;

  detailDialogVisible.value = false;
  emit('onDelete', item);
}

function onDetailOpen(item: Record<string, unknown>) {
  selectedItem.value = item;
  detailDialogVisible.value = true;
}

function onDetailVisibleChange(visible: boolean) {
  if (!visible) selectedItem.value = null;
}

function onEditClick(item: Record<string, unknown> | null) {
  if (!item) return;

  detailDialogVisible.value = false;
  emit('onEdit', item);
}
</script>

<template>
  <div
    v-if="!items.length"
    class="text-subtle px-4 py-8 text-center text-sm"
  >
    No items
  </div>

  <div
    v-else
    class="flex flex-col gap-3"
  >
    <div
      v-for="item in items"
      class="flex items-center gap-4"
      :key="fieldValue(item, itemKey)"
    >
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
        :class="iconBgClass(item)"
      >
        <i :class="[feedIcon(item), 'text-sm']" />
      </div>

      <div class="flex min-w-0 flex-1 flex-col gap-0.5">
        <span class="text-heading text-sm font-medium">{{ fieldValue(item, title.valueKey) }}</span>

        <span class="text-muted text-xs">
          <template v-if="badge">{{ fieldValue(item, badge.valueKey) }}</template>
          <template v-if="badge && subtitle"> · </template>
          <template v-if="subtitle">{{ fieldValue(item, subtitle.valueKey) }}</template>
        </span>
      </div>

      <div class="flex shrink-0 flex-col items-end gap-1">
        <span
          v-if="amount"
          :class="['text-heading text-sm font-semibold', amount.align === 'left' ? 'text-left' : 'text-right']"
        >
          {{ amountValue(item) }}
        </span>

        <div class="flex items-center gap-1">
          <Tag
            v-if="status"
            rounded
            :class="['mr-1', tagClass(item, status)]"
            :icon="statusIconMap?.[fieldValue(item, status.toneKey)]"
            :value="fieldValue(item, status.valueKey)"
          />

          <Button
            v-if="canEdit"
            rounded
            text
            class="h-7 w-7 shrink-0"
            icon="pi pi-pencil"
            severity="secondary"
            @click="onEditClick(item)"
          />

          <Button
            v-if="canArchive"
            rounded
            text
            class="h-7 w-7 shrink-0"
            icon="pi pi-inbox"
            severity="secondary"
            @click="onArchiveClick(item)"
          />

          <Button
            v-if="canDelete"
            rounded
            text
            class="h-7 w-7 shrink-0"
            icon="pi pi-trash"
            severity="danger"
            @click="onDeleteClick(item)"
          />

          <slot
            name="actions"
            :close="closeDetail"
            :item
          />

          <Button
            v-if="showDetails"
            rounded
            text
            class="h-7 w-7 shrink-0"
            icon="pi pi-eye"
            severity="secondary"
            @click="onDetailOpen(item)"
          />
        </div>
      </div>
    </div>
  </div>

  <aside>
    <MMainDialog
      v-model:visible="detailDialogVisible"
      title="common.details"
      :isFooterless="!hasActions"
    >
      <slot
        v-if="selectedItem"
        name="details"
        :item="selectedItem"
      />

      <template #footer>
        <Button
          v-if="canEdit"
          icon="pi pi-pencil"
          :label="$t('common.actions.edit')"
          @click="onEditClick(selectedItem)"
        />

        <Button
          v-if="canArchive"
          icon="pi pi-inbox"
          severity="secondary"
          :label="$t('common.actions.archive')"
          @click="onArchiveClick(selectedItem)"
        />

        <Button
          v-if="canDelete"
          icon="pi pi-trash"
          severity="danger"
          :label="$t('common.actions.delete')"
          @click="onDeleteClick(selectedItem)"
        />

        <slot
          v-if="selectedItem"
          name="actions"
          :close="closeDetail"
          :item="selectedItem"
        />
      </template>
    </MMainDialog>
  </aside>
</template>
