<script setup lang="ts">
import { computed } from 'vue';
import type { Monitor } from '../../../interfaces';
import { MONITOR_STATUS_SEVERITY } from '../../../constants';
import { MONITOR_STATUS, MONITOR_TYPE } from '../../../enums';

const emit = defineEmits<{
  onCopyUrl: [url: string];
  onDeleteRequest: [event: Event];
  onEditRequest: [];
  onPauseRequest: [];
  onViewRequest: [];
}>();

const props = withDefaults(
  defineProps<{
    monitor: Monitor;
    uptime24h?: number | null;
    canDelete?: boolean;
    canPause?: boolean;
    canUpdate?: boolean;
  }>(),
  {
    uptime24h: null,
    canDelete: true,
    canPause: true,
    canUpdate: true,
  }
);

const isHeartbeat = computed(() => props.monitor.type === MONITOR_TYPE.HEARTBEAT);
const isPaused = computed(() => props.monitor.status === MONITOR_STATUS.PAUSED);
const statusSeverity = computed(() => MONITOR_STATUS_SEVERITY[props.monitor.status]);
const uptimeLabel = computed(() => (props.uptime24h === null ? '—' : `${props.uptime24h}%`));

const lastCheckedLabel = computed(() =>
  props.monitor.lastCheckedAt ? new Date(props.monitor.lastCheckedAt).toLocaleString() : null
);

// EVENTS
function onCopyUrlClick() {
  if (!props.monitor.heartbeatUrl) {
    return;
  }

  navigator.clipboard?.writeText(props.monitor.heartbeatUrl);
  emit('onCopyUrl', props.monitor.heartbeatUrl);
}

function onDeleteRequestClick(event: Event) {
  emit('onDeleteRequest', event);
}

function onEditRequestClick() {
  emit('onEditRequest');
}

function onPauseRequestClick() {
  emit('onPauseRequest');
}

function onViewRequestClick() {
  emit('onViewRequest');
}
</script>

<template>
  <Card
    data-testid="monitor-card"
    pt:body="pb-0"
    :class="['bg-panel rounded-lg border', isPaused ? 'border-line' : 'border-line-strong']"
  >
    <template #header>
      <div class="flex items-start justify-between gap-3 px-4 pt-4">
        <div class="flex min-w-0 flex-col gap-1">
          <h3 class="text-heading truncate text-lg font-bold">{{ monitor.name }}</h3>

          <p class="text-subtle truncate text-xs">{{ monitor.target }}</p>
        </div>

        <Tag
          rounded
          :severity="statusSeverity"
          :value="$t(`monitors.status.${monitor.status}`)"
        />
      </div>
    </template>

    <template #content>
      <div class="flex items-center justify-between gap-4 pt-2">
        <div class="flex flex-col">
          <span class="text-subtle text-xs">{{ $t('monitors.uptime24h') }}</span>

          <span class="text-heading text-lg font-semibold">{{ uptimeLabel }}</span>
        </div>

        <div class="flex flex-col text-right">
          <span class="text-subtle text-xs">{{ $t('monitors.lastChecked') }}</span>

          <span class="text-body text-sm">{{ lastCheckedLabel ?? $t('monitors.never') }}</span>
        </div>
      </div>

      <div
        v-if="isHeartbeat && monitor.heartbeatUrl"
        class="mt-3 flex items-center gap-2"
      >
        <code class="bg-panel-muted text-subtle min-w-0 flex-1 truncate rounded-sm px-2 py-1 text-xs">
          {{ monitor.heartbeatUrl }}
        </code>

        <Button
          v-tooltip.top="$t('monitors.actions.copyUrl')"
          rounded
          text
          icon="pi pi-copy"
          severity="secondary"
          @click="onCopyUrlClick"
        />
      </div>
    </template>

    <template #footer>
      <Divider type="dashed" />

      <div class="flex w-full items-center justify-end gap-2">
        <Button
          v-tooltip.top="$t('monitors.actions.view')"
          rounded
          text
          icon="pi pi-eye"
          severity="secondary"
          @click="onViewRequestClick"
        />

        <Button
          v-if="canUpdate"
          v-tooltip.top="$t('monitors.actions.edit')"
          rounded
          text
          icon="pi pi-pencil"
          severity="secondary"
          @click="onEditRequestClick"
        />

        <Button
          v-if="canPause"
          v-tooltip.top="isPaused ? $t('monitors.actions.resume') : $t('monitors.actions.pause')"
          rounded
          text
          severity="secondary"
          :icon="isPaused ? 'pi pi-play' : 'pi pi-pause'"
          @click="onPauseRequestClick"
        />

        <Button
          v-if="canDelete"
          v-tooltip.top="$t('monitors.actions.delete')"
          rounded
          text
          icon="pi pi-trash"
          severity="danger"
          @click="onDeleteRequestClick"
        />
      </div>
    </template>
  </Card>
</template>
