<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import type { Monitor, MonitorCheck, MonitorSeriesPoint, MonitorUptime } from '../../interfaces';
import type { UptimeRange } from '../../types';
import type { Channel } from '@ChannelModule';
import type { Incident } from '@IncidentModule';
import { getI18nRouteName } from '@Helpers';
import { AttachChannelToMonitorService, DetachChannelFromMonitorService, GetChannelsService } from '@ChannelModule';
import { AcknowledgeIncidentService, GetIncidentsService } from '@IncidentModule';
import { MONITOR_STATUS_SEVERITY } from '../../constants';
import { MONITOR_TYPE } from '../../enums';
import {
  GetMonitorChecksService,
  GetMonitorSeriesService,
  GetMonitorService,
  GetMonitorUptimeService,
} from '../../services';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const range = ref<UptimeRange>('7d');

const attachedChannelIds = ref<Set<string>>(new Set());

const channels = ref<Channel[]>([]);
const checks = ref<MonitorCheck[]>([]);
const incidents = ref<Incident[]>([]);
const monitor = ref<Monitor | null>(null);
const series = ref<MonitorSeriesPoint[]>([]);
const uptime = ref<MonitorUptime | null>(null);
const loading = ref(true);

const isHeartbeat = computed(() => monitor.value?.type === MONITOR_TYPE.HEARTBEAT);
const monitorId = computed(() => String(route.params.id));
const statusSeverity = computed(() => (monitor.value ? MONITOR_STATUS_SEVERITY[monitor.value.status] : 'secondary'));

const rangeOptions = computed<UptimeRange[]>(() => ['24h', '7d', '30d', '90d']);
const uptimeRanges = computed<UptimeRange[]>(() => ['24h', '7d', '30d', '90d']);

onMounted(onComponentMount);

// HELPERS
function uptimeLabel(uptimeRange: UptimeRange): string {
  const value = uptime.value?.[uptimeRange];

  return value === null || value === undefined ? '—' : `${value}%`;
}

function loadSeries() {
  GetMonitorSeriesService(monitorId.value, range.value)
    .then(({ data }) => (series.value = data))
    .catch(() => (series.value = []));
}

function checkSeverity(result: string): string {
  return result === 'up' ? 'success' : 'danger';
}

// EVENTS
function onComponentMount() {
  loading.value = true;

  GetMonitorService(monitorId.value)
    .then(({ data }) => {
      monitor.value = data;
      attachedChannelIds.value = new Set(data.channelIds ?? []);
    })
    .catch(() => (monitor.value = null))
    .finally(() => (loading.value = false));

  GetMonitorUptimeService(monitorId.value)
    .then(({ data }) => (uptime.value = data))
    .catch(() => (uptime.value = null));

  GetMonitorChecksService(monitorId.value)
    .then(({ data }) => (checks.value = data))
    .catch(() => (checks.value = []));

  GetIncidentsService({ monitorId: monitorId.value })
    .then(({ data }) => (incidents.value = data))
    .catch(() => (incidents.value = []));

  GetChannelsService()
    .then(({ data }) => (channels.value = data))
    .catch(() => (channels.value = []));

  loadSeries();
}

function onAttachChannel(channel: Channel) {
  AttachChannelToMonitorService(channel.id, monitorId.value)
    .then(() => {
      attachedChannelIds.value = new Set(attachedChannelIds.value).add(channel.id);
      toast.add({ severity: 'success', summary: t('monitors.detail.notifications.attached', { name: channel.name }), life: 4000 });
    })
    .catch(() => toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 }));
}

function onDetachChannel(channel: Channel) {
  DetachChannelFromMonitorService(channel.id, monitorId.value)
    .then(() => {
      const next = new Set(attachedChannelIds.value);
      next.delete(channel.id);
      attachedChannelIds.value = next;
      toast.add({ severity: 'success', summary: t('monitors.detail.notifications.detached', { name: channel.name }), life: 4000 });
    })
    .catch(() => toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 }));
}

function onAcknowledgeIncident(incident: Incident) {
  AcknowledgeIncidentService(incident.id)
    .then(({ data }) => {
      incidents.value = incidents.value.map((existing) => (existing.id === data.id ? data : existing));
      toast.add({ severity: 'success', summary: t('incidents.acknowledgedSuccess'), life: 5000 });
    })
    .catch(() => toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 }));
}

function onRangeChange(value: UptimeRange) {
  range.value = value;
  loadSeries();
}

function onBack() {
  router.push({ name: getI18nRouteName('monitors') });
}

function onCopyUrl() {
  if (!monitor.value?.heartbeatUrl) {
    return;
  }

  navigator.clipboard?.writeText(monitor.value.heartbeatUrl);
  toast.add({ severity: 'success', summary: t('monitors.urlCopied'), life: 3000 });
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader :title="monitor?.name ?? 'monitors.title'">
        <template #actions>
          <Button
            text
            icon="pi pi-arrow-left"
            severity="secondary"
            :label="$t('monitors.detail.back')"
            @click="onBack"
          />
        </template>
      </ThePageHeader>
    </template>

    <main class="flex flex-col gap-6">
      <div
        v-if="loading"
        class="flex flex-col gap-4"
      >
        <Skeleton
          height="2rem"
          width="40%"
        />

        <Skeleton
          height="6rem"
          width="100%"
        />
      </div>

      <div
        v-else-if="!monitor"
        class="border-line bg-panel text-subtle rounded-xl border p-6 text-center"
      >
        {{ $t('monitors.detail.notFound') }}
      </div>

      <template v-else>
        <section class="border-line bg-panel flex flex-col gap-3 rounded-lg border p-4">
          <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 flex-col gap-1">
              <h2 class="text-heading truncate text-xl font-bold">{{ monitor.name }}</h2>

              <p class="text-subtle truncate text-sm">{{ monitor.target }}</p>
            </div>

            <Tag
              rounded
              :severity="statusSeverity"
              :value="$t(`monitors.status.${monitor.status}`)"
            />
          </div>

          <div
            v-if="isHeartbeat && monitor.heartbeatUrl"
            class="flex flex-col gap-1"
          >
            <span class="text-subtle text-xs">{{ $t('monitors.detail.pingUrl') }}</span>

            <div class="flex items-center gap-2">
              <code class="bg-panel-muted text-subtle min-w-0 flex-1 truncate rounded-sm px-2 py-1 text-xs">
                {{ monitor.heartbeatUrl }}
              </code>

              <Button
                v-tooltip.top="$t('monitors.actions.copyUrl')"
                rounded
                text
                icon="pi pi-copy"
                severity="secondary"
                @click="onCopyUrl"
              />
            </div>
          </div>
        </section>

        <section
          class="grid grid-cols-2 gap-4 lg:grid-cols-4"
          data-testid="uptime-cards"
        >
          <div
            v-for="summaryRange in uptimeRanges"
            class="border-line bg-panel flex flex-col gap-1 rounded-lg border p-4"
            :key="summaryRange"
          >
            <span class="text-subtle text-xs uppercase">{{ summaryRange }}</span>

            <span class="text-heading text-2xl font-semibold">{{ uptimeLabel(summaryRange) }}</span>

            <span class="text-subtle text-xs">{{ $t('monitors.detail.uptime') }}</span>
          </div>
        </section>

        <section class="flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h3 class="text-heading text-lg font-semibold">{{ $t('monitors.detail.charts') }}</h3>

            <SelectButton
              optionLabel="label"
              optionValue="value"
              :allowEmpty="false"
              :modelValue="range"
              :options="rangeOptions.map((option) => ({ label: option, value: option }))"
              @update:modelValue="onRangeChange"
            />
          </div>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="border-line bg-panel rounded-lg border p-4">
              <h4 class="text-muted mb-2 text-sm">{{ $t('monitors.detail.latency') }}</h4>

              <OMonitorChart
                variant="latency"
                :series
              />
            </div>

            <div class="border-line bg-panel rounded-lg border p-4">
              <h4 class="text-muted mb-2 text-sm">{{ $t('monitors.detail.uptimeTrend') }}</h4>

              <OMonitorChart
                variant="uptime"
                :series
              />
            </div>
          </div>
        </section>

        <section class="flex flex-col gap-2">
          <h3 class="text-heading text-lg font-semibold">{{ $t('monitors.detail.recentChecks') }}</h3>

          <DataTable
            scrollable
            stripedRows
            data-testid="recent-checks"
            scrollHeight="320px"
            :value="checks"
          >
            <template #empty>
              <span class="text-subtle text-sm">{{ $t('monitors.detail.noChecks') }}</span>
            </template>

            <Column
              field="checkedAt"
              :header="$t('monitors.detail.checkedAt')"
            />

            <Column :header="$t('monitors.detail.result')">
              <template #body="{ data }">
                <Tag
                  rounded
                  :severity="checkSeverity(data.result)"
                  :value="$t(`monitors.status.${data.result}`)"
                />
              </template>
            </Column>

            <Column
              field="responseTimeMs"
              :header="$t('monitors.detail.responseTime')"
            />

            <Column
              field="statusCode"
              :header="$t('monitors.detail.statusCode')"
            />

            <Column
              field="error"
              :header="$t('monitors.detail.error')"
            />
          </DataTable>
        </section>

        <section class="flex flex-col gap-2">
          <h3 class="text-heading text-lg font-semibold">{{ $t('incidents.title') }}</h3>

          <DataTable
            data-testid="incident-history"
            :value="incidents"
          >
            <template #empty>
              <span class="text-subtle text-sm">{{ $t('incidents.empty') }}</span>
            </template>

            <Column
              field="startedAt"
              :header="$t('incidents.startedAt')"
            />

            <Column :header="$t('incidents.status')">
              <template #body="{ data }">
                <Tag
                  rounded
                  :severity="data.isOpen ? 'danger' : 'success'"
                  :value="data.isOpen ? $t('incidents.statusOpen') : $t('incidents.statusResolved')"
                />
              </template>
            </Column>

            <Column
              field="cause"
              :header="$t('incidents.cause')"
            />

            <Column :header="$t('incidents.acknowledge')">
              <template #body="{ data }">
                <Tag
                  v-if="data.acknowledgedAt"
                  :value="$t('incidents.acknowledged')"
                />

                <Button
                  v-else
                  size="small"
                  :label="$t('incidents.acknowledge')"
                  @click="onAcknowledgeIncident(data)"
                />
              </template>
            </Column>
          </DataTable>
        </section>

        <section
          class="flex flex-col gap-2"
          data-testid="notifications"
        >
          <h3 class="text-heading text-lg font-semibold">{{ $t('monitors.detail.notifications.title') }}</h3>

          <p class="text-subtle text-xs">{{ $t('monitors.detail.notifications.hint') }}</p>

          <div
            v-if="!channels.length"
            class="border-line bg-panel text-subtle rounded-lg border p-4 text-sm"
          >
            {{ $t('monitors.detail.notifications.noChannels') }}
          </div>

          <div
            v-else
            class="flex flex-col gap-2"
          >
            <div
              v-for="channel in channels"
              class="border-line bg-panel flex items-center justify-between gap-3 rounded-lg border p-3"
              :key="channel.id"
            >
              <span class="text-body text-sm">{{ channel.name }} · {{ $t(`channels.type.${channel.type}`) }}</span>

              <Button
                v-if="attachedChannelIds.has(channel.id)"
                severity="secondary"
                size="small"
                :label="$t('monitors.detail.notifications.detach')"
                @click="onDetachChannel(channel)"
              />

              <Button
                v-else
                size="small"
                :label="$t('monitors.detail.notifications.attach')"
                @click="onAttachChannel(channel)"
              />
            </div>
          </div>
        </section>
      </template>
    </main>
  </TheLayout>
</template>
