<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import type { Monitor } from '../../interfaces';
import { getI18nRouteName } from '@Helpers';
import { useProjectStore } from '@ProjectModule';
import { MONITOR_STATUS } from '../../enums';
import { DeleteMonitorService, UpdateMonitorService } from '../../services';
import { useMonitorStore } from '../../store';

const monitorStore = useMonitorStore();
const projectStore = useProjectStore();

const confirm = useConfirm();
const router = useRouter();
const { t } = useI18n();
const toast = useToast();

const { loading, monitors } = storeToRefs(monitorStore);
const { activeProjectId } = storeToRefs(projectStore);

const dialogVisible = ref(false);
const editingMonitor = ref<Monitor | null>(null);

// Monitor management is available to any authenticated user — the backend
// monitor routes are auth-only (no per-action permission). Creating still
// requires an active project to scope the monitor to.
const canCreate = computed(() => !!activeProjectId.value);

watch(activeProjectId, onActiveProjectChange);

onMounted(onMonitorsViewMounted);

// HELPERS
function loadMonitors() {
  if (!activeProjectId.value) {
    return;
  }

  monitorStore.fetchForProject(activeProjectId.value).catch(() => notifyError());
}

function notifyError() {
  toast.add({
    severity: 'error',
    summary: t('errors.somethingWentWrong'),
    detail: t('errors.refreshAndContactAdmin'),
    life: 10000,
  });
}

// EVENTS
function onActiveProjectChange() {
  loadMonitors();
}

function onMonitorsViewMounted() {
  if (activeProjectId.value) {
    loadMonitors();
    return;
  }

  projectStore.fetchProjects().then(loadMonitors).catch(() => notifyError());
}

function onCopyUrl() {
  toast.add({ severity: 'success', summary: t('monitors.urlCopied'), life: 3000 });
}

function onCreateRequest() {
  editingMonitor.value = null;
  dialogVisible.value = true;
}

function onDialogSuccess(monitor: Monitor) {
  dialogVisible.value = false;
  editingMonitor.value = null;

  const exists = monitors.value.some((current) => current.id === monitor.id);

  if (exists) {
    monitorStore.replaceMonitor(monitor);
    return;
  }

  monitorStore.addMonitor(monitor);
}

function onEditRequest(monitor: Monitor) {
  editingMonitor.value = monitor;
  dialogVisible.value = true;
}

function onViewRequest(monitor: Monitor) {
  router.push({ name: getI18nRouteName('monitor-detail'), params: { id: monitor.id } });
}

function onDeleteRequest(event: Event, monitor: Monitor) {
  confirm.require({
    accept: onDeleteAccept,
    acceptProps: { label: t('monitors.actions.delete'), class: 'celer-button-danger' },
    group: 'delete-monitor',
    message: t('monitors.deleteConfirm', { name: monitor.name }),
    rejectProps: { label: t('common.actions.cancel'), class: 'celer-button-secondary' },
    target: event.currentTarget as HTMLElement,
  });

  function onDeleteAccept() {
    DeleteMonitorService(monitor.id)
      .then(({ data }) => {
        monitorStore.removeMonitor(data.id);
        toast.add({ severity: 'success', summary: t('monitors.deleted', { name: monitor.name }), life: 5000 });
      })
      .catch(() => notifyError());
  }
}

function onPauseRequest(monitor: Monitor) {
  const isPaused = monitor.status === MONITOR_STATUS.PAUSED;
  const status = isPaused ? MONITOR_STATUS.PENDING : MONITOR_STATUS.PAUSED;

  UpdateMonitorService(monitor.id, { status })
    .then(({ data }) => {
      monitorStore.replaceMonitor(data);
      toast.add({ severity: 'success', summary: t(isPaused ? 'monitors.resumed' : 'monitors.paused'), life: 5000 });
    })
    .catch(() => notifyError());
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="monitors.title">
        <template
          v-if="!dialogVisible"
          #actions
        >
          <Button
            v-if="canCreate"
            class="celer-button-soft-primary w-fit shadow transition-all duration-300 active:scale-95"
            data-testid="add-monitor"
            icon="pi pi-plus"
            :label="$t('monitors.actions.create')"
            @click="onCreateRequest"
          />
        </template>
      </ThePageHeader>
    </template>

    <main>
      <div
        v-if="loading"
        class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="skeletonIndex in 6"
          class="border-line flex flex-col gap-3 rounded-lg border p-4"
          :key="skeletonIndex"
        >
          <Skeleton
            height="1.25rem"
            width="60%"
          />

          <Skeleton
            height="1rem"
            width="40%"
          />

          <Skeleton
            height="2.25rem"
            width="100%"
          />
        </div>
      </div>

      <div
        v-else-if="monitors.length"
        class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
      >
        <MMonitorCard
          v-for="monitor in monitors"
          :key="monitor.id"
          :monitor
          @onCopyUrl="onCopyUrl"
          @onDeleteRequest="(event: Event) => onDeleteRequest(event, monitor)"
          @onEditRequest="onEditRequest(monitor)"
          @onPauseRequest="onPauseRequest(monitor)"
          @onViewRequest="onViewRequest(monitor)"
        />
      </div>

      <AEmptyState
        v-else
        :description="$t('monitors.empty')"
        :title="$t('monitors.title')"
      />
    </main>

    <aside>
      <ConfirmPopup
        class="max-w-70 p-2 text-center"
        group="delete-monitor"
        pt:footer="justify-between pt-2"
      />

      <MMainDialog
        v-model:visible="dialogVisible"
        isFooterless
        :title="editingMonitor ? 'monitors.form.editHeader' : 'monitors.form.createHeader'"
      >
        <MAddEditMonitorForm
          v-if="activeProjectId"
          :key="editingMonitor?.id ?? 'new'"
          :monitor="editingMonitor"
          :projectId="activeProjectId"
          @onClose="dialogVisible = false"
          @onSuccess="onDialogSuccess"
        />
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
