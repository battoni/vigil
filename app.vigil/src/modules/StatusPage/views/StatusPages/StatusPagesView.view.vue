<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import type { StatusPage } from '../../interfaces';
import type { Monitor } from '@MonitorModule';
import { GetMonitorsService } from '@MonitorModule';
import {
  AttachMonitorToStatusPageService,
  DeleteStatusPageService,
  DetachMonitorFromStatusPageService,
  GetStatusPageService,
} from '../../services';
import { useStatusPageStore } from '../../store';

const statusPageStore = useStatusPageStore();

const confirm = useConfirm();
const { t } = useI18n();
const toast = useToast();

const { loading, statusPages } = storeToRefs(statusPageStore);

const attachedMonitorIds = ref<Set<string>>(new Set());

const allMonitors = ref<Monitor[]>([]);
const dialogVisible = ref(false);
const editingStatusPage = ref<StatusPage | null>(null);
const manageDialogVisible = ref(false);
const managingStatusPage = ref<StatusPage | null>(null);

onMounted(onComponentMount);

// HELPERS
function publicUrl(statusPage: StatusPage): string {
  return `/status/${statusPage.slug}`;
}

function notifyError() {
  toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 });
}

// EVENTS
function onComponentMount() {
  statusPageStore.fetchStatusPages().catch(() => notifyError());
}

function onCreateRequest() {
  editingStatusPage.value = null;
  dialogVisible.value = true;
}

function onEditRequest(statusPage: StatusPage) {
  editingStatusPage.value = statusPage;
  dialogVisible.value = true;
}

function onManageRequest(statusPage: StatusPage) {
  managingStatusPage.value = statusPage;
  manageDialogVisible.value = true;
  attachedMonitorIds.value = new Set();

  GetStatusPageService(statusPage.id)
    .then(({ data }) => (attachedMonitorIds.value = new Set((data.monitors ?? []).map((monitor) => monitor.id))))
    .catch(() => notifyError());

  GetMonitorsService()
    .then(({ data }) => (allMonitors.value = data))
    .catch(() => (allMonitors.value = []));
}

function onAttachMonitor(monitor: Monitor) {
  if (!managingStatusPage.value) {
    return;
  }

  AttachMonitorToStatusPageService(managingStatusPage.value.id, monitor.id)
    .then(() => {
      attachedMonitorIds.value = new Set(attachedMonitorIds.value).add(monitor.id);
      toast.add({ severity: 'success', summary: t('statusPages.monitors.attached', { name: monitor.name }), life: 4000 });
    })
    .catch(() => notifyError());
}

function onDetachMonitor(monitor: Monitor) {
  if (!managingStatusPage.value) {
    return;
  }

  DetachMonitorFromStatusPageService(managingStatusPage.value.id, monitor.id)
    .then(() => {
      const next = new Set(attachedMonitorIds.value);
      next.delete(monitor.id);
      attachedMonitorIds.value = next;
      toast.add({ severity: 'success', summary: t('statusPages.monitors.detached', { name: monitor.name }), life: 4000 });
    })
    .catch(() => notifyError());
}

function onDialogSuccess(statusPage: StatusPage) {
  dialogVisible.value = false;
  editingStatusPage.value = null;

  const exists = statusPages.value.some((current) => current.id === statusPage.id);

  if (exists) {
    statusPageStore.replaceStatusPage(statusPage);
    return;
  }

  statusPageStore.addStatusPage(statusPage);
}

function onDeleteRequest(event: Event, statusPage: StatusPage) {
  confirm.require({
    accept: onDeleteAccept,
    acceptProps: { label: t('statusPages.delete'), class: 'celer-button-danger' },
    group: 'delete-status-page',
    message: t('statusPages.deleteConfirm', { name: statusPage.name }),
    rejectProps: { label: t('common.actions.cancel'), class: 'celer-button-secondary' },
    target: event.currentTarget as HTMLElement,
  });

  function onDeleteAccept() {
    DeleteStatusPageService(statusPage.id)
      .then(({ data }) => {
        statusPageStore.removeStatusPage(data.id);
        toast.add({ severity: 'success', summary: t('statusPages.deleted', { name: statusPage.name }), life: 5000 });
      })
      .catch(() => notifyError());
  }
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="statusPages.title">
        <template
          v-if="!dialogVisible"
          #actions
        >
          <Button
            class="celer-button-soft-primary w-fit shadow"
            data-testid="add-status-page"
            icon="pi pi-plus"
            :label="$t('statusPages.add')"
            @click="onCreateRequest"
          />
        </template>
      </ThePageHeader>
    </template>

    <main>
      <DataTable
        data-testid="status-pages-table"
        :loading
        :value="statusPages"
      >
        <template #empty>
          <span class="text-subtle text-sm">{{ $t('statusPages.empty') }}</span>
        </template>

        <Column
          field="name"
          :header="$t('statusPages.name')"
        />

        <Column
          field="slug"
          :header="$t('statusPages.slug')"
        />

        <Column :header="$t('statusPages.visibility')">
          <template #body="{ data }">
            <Tag
              rounded
              :severity="data.isPublic ? 'success' : 'secondary'"
              :value="data.isPublic ? $t('statusPages.public') : $t('statusPages.private')"
            />
          </template>
        </Column>

        <Column :header="$t('statusPages.actions')">
          <template #body="{ data }">
            <span class="flex justify-end gap-2">
              <a
                class="text-primary-600 flex items-center"
                target="_blank"
                :href="publicUrl(data)"
              >
                <i class="pi pi-external-link" />
              </a>

              <Button
                v-tooltip.top="$t('statusPages.monitors.manage')"
                rounded
                text
                data-testid="manage-status-monitors"
                icon="pi pi-sitemap"
                severity="secondary"
                @click="onManageRequest(data)"
              />

              <Button
                rounded
                text
                icon="pi pi-pencil"
                severity="secondary"
                @click="onEditRequest(data)"
              />

              <Button
                rounded
                text
                icon="pi pi-trash"
                severity="danger"
                @click="onDeleteRequest($event, data)"
              />
            </span>
          </template>
        </Column>
      </DataTable>
    </main>

    <aside>
      <ConfirmPopup
        class="max-w-70 p-2 text-center"
        group="delete-status-page"
        pt:footer="justify-between pt-2"
      />

      <MMainDialog
        v-model:visible="dialogVisible"
        isFooterless
        :title="editingStatusPage ? 'statusPages.form.editHeader' : 'statusPages.form.createHeader'"
      >
        <MAddEditStatusPageForm
          :key="editingStatusPage?.id ?? 'new'"
          :statusPage="editingStatusPage"
          @onClose="dialogVisible = false"
          @onSuccess="onDialogSuccess"
        />
      </MMainDialog>

      <MMainDialog
        v-model:visible="manageDialogVisible"
        isFooterless
        title="statusPages.monitors.title"
      >
        <div
          class="flex flex-col gap-2"
          data-testid="manage-monitors"
        >
          <div
            v-if="!allMonitors.length"
            class="border-line bg-panel text-subtle rounded-lg border p-4 text-sm"
          >
            {{ $t('statusPages.monitors.empty') }}
          </div>

          <div
            v-for="monitor in allMonitors"
            class="border-line bg-panel flex items-center justify-between gap-3 rounded-lg border p-3"
            :key="monitor.id"
          >
            <span class="text-body text-sm">{{ monitor.name }}</span>

            <Button
              v-if="attachedMonitorIds.has(monitor.id)"
              severity="secondary"
              size="small"
              :label="$t('statusPages.monitors.detach')"
              @click="onDetachMonitor(monitor)"
            />

            <Button
              v-else
              size="small"
              :label="$t('statusPages.monitors.attach')"
              @click="onAttachMonitor(monitor)"
            />
          </div>
        </div>
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
