<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import type { Monitor, MonitorUptime } from '../../interfaces';
import type { UptimeRange } from '../../types';
import { getI18nRouteName } from '@Helpers';
import { MONITOR_STATUS_SEVERITY } from '../../constants';
import { MONITOR_TYPE } from '../../enums';
import { GetMonitorService, GetMonitorUptimeService } from '../../services';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const monitor = ref<Monitor | null>(null);
const uptime = ref<MonitorUptime | null>(null);
const loading = ref(true);

const isHeartbeat = computed(() => monitor.value?.type === MONITOR_TYPE.HEARTBEAT);
const monitorId = computed(() => String(route.params.id));
const statusSeverity = computed(() => (monitor.value ? MONITOR_STATUS_SEVERITY[monitor.value.status] : 'secondary'));

const uptimeRanges = computed<UptimeRange[]>(() => ['24h', '7d', '30d', '90d']);

onMounted(onComponentMount);

// HELPERS
function uptimeLabel(range: UptimeRange): string {
  const value = uptime.value?.[range];

  return value === null || value === undefined ? '—' : `${value}%`;
}

// EVENTS
function onComponentMount() {
  loading.value = true;

  GetMonitorService(monitorId.value)
    .then(({ data }) => (monitor.value = data))
    .catch(() => (monitor.value = null))
    .finally(() => (loading.value = false));

  GetMonitorUptimeService(monitorId.value)
    .then(({ data }) => (uptime.value = data))
    .catch(() => (uptime.value = null));
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
            v-for="range in uptimeRanges"
            class="border-line bg-panel flex flex-col gap-1 rounded-lg border p-4"
            :key="range"
          >
            <span class="text-subtle text-xs uppercase">{{ range }}</span>

            <span class="text-heading text-2xl font-semibold">{{ uptimeLabel(range) }}</span>

            <span class="text-subtle text-xs">{{ $t('monitors.detail.uptime') }}</span>
          </div>
        </section>
      </template>
    </main>
  </TheLayout>
</template>
