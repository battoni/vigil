<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { PublicStatus } from '../../interfaces';
import { GetPublicStatusService } from '../../services';

const route = useRoute();

const STATUS_SEVERITY: Record<string, string> = { up: 'success', down: 'danger' };

const page = ref<PublicStatus | null>(null);
const loading = ref(true);
const notFound = ref(false);

const headline = computed(() => (page.value?.branding?.headline as string) ?? '');
const isOperational = computed(() => page.value?.overallStatus === 'operational');
const slug = computed(() => String(route.params.slug));

onMounted(onComponentMount);

// HELPERS
function monitorSeverity(status: string): string {
  return STATUS_SEVERITY[status] ?? 'secondary';
}

function uptimeLabel(value: number | null | undefined): string {
  return value === null || value === undefined ? '—' : `${value}%`;
}

// EVENTS
function onComponentMount() {
  loading.value = true;

  GetPublicStatusService(slug.value)
    .then(({ data }) => (page.value = data))
    .catch(() => (notFound.value = true))
    .finally(() => (loading.value = false));
}
</script>

<template>
  <div class="bg-canvas min-h-screen w-full">
    <div class="mx-auto flex w-full max-w-[800px] flex-col gap-6 px-6 py-12">
      <div
        v-if="loading"
        class="flex flex-col gap-4"
      >
        <Skeleton
          height="2rem"
          width="50%"
        />

        <Skeleton
          height="8rem"
          width="100%"
        />
      </div>

      <div
        v-else-if="notFound || !page"
        class="border-line bg-panel text-subtle rounded-xl border p-8 text-center"
      >
        {{ $t('public.notFound') }}
      </div>

      <template v-else>
        <header class="flex flex-col gap-2">
          <h1 class="text-heading text-2xl font-bold">{{ page.name }}</h1>

          <p
            v-if="headline"
            class="text-muted text-sm"
          >
            {{ headline }}
          </p>
        </header>

        <div
          data-testid="overall-status"
          :class="[
            'flex items-center gap-3 rounded-xl border p-4',
            isOperational ? 'border-success-200 bg-success-50' : 'border-danger-200 bg-danger-50',
          ]"
        >
          <i :class="[isOperational ? 'pi pi-check-circle text-success-600' : 'pi pi-exclamation-circle text-danger-600', 'text-xl']" />

          <span class="text-heading font-semibold">
            {{ isOperational ? $t('public.operational') : $t('public.degraded') }}
          </span>
        </div>

        <section
          v-for="group in page.groups"
          class="flex flex-col gap-2"
          :key="group.name"
        >
          <h2 class="text-muted text-sm font-semibold uppercase tracking-wide">{{ group.name }}</h2>

          <div
            v-for="monitor in group.monitors"
            class="border-line bg-panel flex items-center justify-between gap-3 rounded-lg border p-3"
            :key="monitor.name"
          >
            <div class="flex items-center gap-2">
              <Tag
                rounded
                :severity="monitorSeverity(monitor.status)"
                :value="$t(`monitors.status.${monitor.status}`)"
              />

              <span class="text-body text-sm">{{ monitor.name }}</span>
            </div>

            <span class="text-subtle text-sm">{{ uptimeLabel(monitor.uptime['90d']) }}</span>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>
